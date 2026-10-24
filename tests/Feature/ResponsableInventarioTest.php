<?php

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Resources\Almacens\Pages\ListAlmacens;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\PublicacionExistencias\Pages\ListPublicacionExistencias;
use App\Filament\Resources\PublicacionExistencias\PublicacionExistenciaResource;
use App\Filament\Resources\ReservaStocks\Pages\ListReservaStocks;
use App\Filament\Resources\StockSaldos\Pages\ListStockSaldos;
use App\Models\Almacen;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\Producto;
use App\Models\PublicacionExistencia;
use App\Models\ReservaStock;
use App\Models\StockSaldo;
use App\Models\User;
use App\Services\OrdenService;
use App\Services\PublicacionExistenciaService;
use App\Support\DocumentoNumero;
use Database\Seeders\OrdenEstadoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->seed(RolesPermisosSeeder::class);
});

/**
 * @param  array<int, array{producto_id: int, almacen_id: int, cantidad: int, precio_unitario: int}>  $lineas
 */
function cargaInicialInventario(User $usuario, array $lineas): PublicacionExistencia
{
    $carga = PublicacionExistencia::query()->create([
        'numero' => DocumentoNumero::generar('PUB'),
        'fecha_ingreso' => now(),
        'responsable_user_id' => $usuario->getKey(),
        'estado' => PublicacionExistencia::ESTADO_BORRADOR,
    ]);
    $carga->existencias()->createMany($lineas);

    return $carga;
}

test('el responsable crea y edita productos con sus caracteristicas', function (): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $this->actingAs($usuario);

    Livewire::test(ListProductos::class)
        ->callAction('create', data: [
            'nombre' => 'Arroz',
            'codigo' => 'ARR-001',
            'unidad_medida' => 'bolsa',
            'caracteristicas' => 'Bolsa de 5 kg',
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();
    $producto = Producto::query()->sole();
    expect($producto->caracteristicas)->toBe('Bolsa de 5 kg');

    Livewire::test(ListProductos::class)
        ->callAction(TestAction::make('edit')->table($producto), data: ['caracteristicas' => 'Bolsa de 10 kg'])
        ->assertHasNoActionErrors();

    expect($producto->fresh()->caracteristicas)->toBe('Bolsa de 10 kg');
});

test('el responsable registra y actualiza los almacenes del inventario', function (): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $this->actingAs($usuario);

    Livewire::test(ListAlmacens::class)
        ->callAction('create', data: ['nombre' => 'Almacén central', 'ubicacion' => 'Planta baja', 'is_active' => true])
        ->assertHasNoActionErrors();
    $almacen = Almacen::query()->sole();

    Livewire::test(ListAlmacens::class)
        ->callAction(TestAction::make('edit')->table($almacen), data: ['ubicacion' => 'Primer piso'])
        ->assertHasNoActionErrors();

    expect($almacen->fresh()->ubicacion)->toBe('Primer piso');
});

test('la carga queda en borrador hasta confirmar e inicializa stock por almacen', function (string $rol): void {
    $usuario = User::factory()->create()->assignRole($rol);
    $producto = Producto::factory()->create();
    $almacenes = Almacen::factory()->count(2)->create();
    $this->actingAs($usuario);

    Livewire::test(ListPublicacionExistencias::class)
        ->callAction('create', data: [
            'fecha_ingreso' => now()->toDateTimeString(),
            'estado' => PublicacionExistencia::ESTADO_PUBLICADA,
            'responsable_user_id' => User::factory()->create()->getKey(),
            'existencias' => [
                ['producto_id' => $producto->getKey(), 'almacen_id' => $almacenes[0]->getKey(), 'cantidad' => 20, 'precio_unitario' => 12],
                ['producto_id' => $producto->getKey(), 'almacen_id' => $almacenes[1]->getKey(), 'cantidad' => 7, 'precio_unitario' => 12],
            ],
        ])
        ->assertHasNoActionErrors();

    $carga = PublicacionExistencia::query()->sole();
    expect($carga->estado)->toBe(PublicacionExistencia::ESTADO_BORRADOR)
        ->and($carga->responsable_user_id)->toBe($usuario->getKey());
    $this->assertDatabaseCount('stock_saldos', 0);

    Livewire::test(ListPublicacionExistencias::class)
        ->callAction(TestAction::make('publicar')->table($carga))
        ->assertNotified('Ingreso confirmado');

    expect($carga->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_PUBLICADA);
    $this->assertDatabaseHas('stock_saldos', [
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacenes[0]->getKey(),
        'cantidad_actual' => 20, 'cantidad_reservada' => 0,
    ]);
    $this->assertDatabaseHas('stock_saldos', [
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacenes[1]->getKey(),
        'cantidad_actual' => 7, 'cantidad_reservada' => 0,
    ]);

    Livewire::test(ListPublicacionExistencias::class)
        ->assertActionHidden(TestAction::make('edit')->table($carga))
        ->assertActionHidden(TestAction::make('delete')->table($carga))
        ->assertActionHidden(TestAction::make('publicar')->table($carga));
})->with(['responsable_inventario', 'administrador', 'super_admin']);

test('la carga no admite productos duplicados en el mismo almacen', function (): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $linea = ['producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(), 'cantidad' => 5, 'precio_unitario' => 10];
    $this->actingAs($usuario);

    Livewire::test(ListPublicacionExistencias::class)
        ->callAction('create', data: ['fecha_ingreso' => now()->toDateTimeString(), 'existencias' => [$linea, $linea]])
        ->assertHasActionErrors(['existencias']);

    $this->assertDatabaseCount('publicaciones_existencias', 0);
    $this->assertDatabaseCount('stock_saldos', 0);
});

test('una carga conflictiva revierte todas las lineas y conserva el stock y las reservas', function (): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $productos = Producto::factory()->count(2)->create();
    $almacen = Almacen::factory()->create();
    $saldo = StockSaldo::query()->create([
        'producto_id' => $productos[1]->getKey(), 'almacen_id' => $almacen->getKey(),
        'cantidad_actual' => 15, 'cantidad_reservada' => 4,
        'precio_venta' => 10,
    ]);
    $carga = cargaInicialInventario($usuario, $productos->map(fn (Producto $producto): array => [
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(),
        'cantidad' => 100, 'precio_unitario' => 10,
    ])->all());

    app(PublicacionExistenciaService::class)->registrarCargaInicial($carga, $usuario);

    $this->assertDatabaseCount('stock_saldos', 2);
    expect($saldo->fresh()->cantidad_actual)->toBe(115)
        ->and($saldo->fresh()->cantidad_reservada)->toBe(4)
        ->and($carga->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_PUBLICADA)
        ->and($saldo->fresh()->precio_venta)->toBe('10.00');
});

test('confirmar la misma carga dos veces no duplica el stock', function (): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $carga = cargaInicialInventario($usuario, [[
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(), 'cantidad' => 9, 'precio_unitario' => 2,
    ]]);
    app(PublicacionExistenciaService::class)->registrarCargaInicial($carga, $usuario);

    expect(fn () => app(PublicacionExistenciaService::class)->registrarCargaInicial($carga, $usuario))
        ->toThrow(AuthorizationException::class);

    expect(StockSaldo::query()->sole()->cantidad_actual)->toBe(9);
});

test('el responsable consulta stock fisico reservado y disponible filtrado por almacen', function (): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacenes = Almacen::factory()->count(2)->create();
    $saldos = $almacenes->map(fn (Almacen $almacen): StockSaldo => StockSaldo::query()->create([
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(),
        'cantidad_actual' => 20, 'cantidad_reservada' => 6,
    ]));
    $this->actingAs($usuario);

    Livewire::test(ListStockSaldos::class)
        ->assertCanSeeTableRecords($saldos)
        ->assertTableColumnStateSet('cantidad_actual', 20, $saldos[0])
        ->assertTableColumnStateSet('cantidad_reservada', 6, $saldos[0])
        ->assertTableColumnStateSet('disponible', 14, $saldos[0])
        ->filterTable('almacen_id', $almacenes[0]->getKey())
        ->assertCanSeeTableRecords([$saldos[0]])
        ->assertCanNotSeeTableRecords([$saldos[1]]);

    expect(Gate::forUser($usuario)->allows('viewAny', ReservaStock::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->allows('update', $saldos[0]))->toBeFalse();
});

test('otros roles no pueden registrar una carga inicial', function (string $rol): void {
    $usuario = User::factory()->create()->assignRole($rol);
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $carga = cargaInicialInventario($responsable, [[
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(), 'cantidad' => 3, 'precio_unitario' => 2,
    ]]);

    expect(Gate::forUser($usuario)->allows('create', PublicacionExistencia::class))->toBeFalse();
    expect(fn () => app(PublicacionExistenciaService::class)->registrarCargaInicial($carga, $usuario))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('stock_saldos', 0);
    expect($carga->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_BORRADOR);
})->with(['cliente', 'encargado_ventas', 'encargado_almacen']);

test('la ruta de existencias muestra las cargas y exige autorizacion', function (): void {
    config(['app.env' => 'local']);
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $url = PublicacionExistenciaResource::getUrl();

    $this->actingAs($usuario)->get($url)->assertOk()->assertSeeLivewire(ListPublicacionExistencias::class);
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
});

test('ventas puede reservar el stock inicial y el responsable consulta el saldo resultante', function (): void {
    $this->seed(OrdenEstadoSeeder::class);
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $ventas = User::factory()->create()->assignRole('encargado_ventas');
    $cliente = User::factory()->create()->assignRole('cliente');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $carga = cargaInicialInventario($responsable, [[
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(), 'cantidad' => 20, 'precio_unitario' => 10,
    ]]);
    app(PublicacionExistenciaService::class)->registrarCargaInicial($carga, $responsable);
    $orden = Orden::query()->create([
        'codigo_orden' => (string) Str::uuid(),
        'numero' => DocumentoNumero::generar('ORD'),
        'razon_social' => 'Cliente de prueba',
        'nit' => '123456789',
        'almacen_id' => $almacen->getKey(),
        'orden_estado_id' => OrdenEstado::query()->where('codigo', OrdenEstadoCodigo::PendienteAprobacion->value)->value('orden_estado_id'),
        'solicitante_user_id' => $cliente->getKey(),
        'fecha_registro' => now(),
    ]);
    $orden->productos()->create([
        'existencia_id' => $carga->existencias()->sole()->getKey(),
        'producto_id' => $producto->getKey(), 'cantidad_solicitada' => 6, 'precio_unitario' => 10,
    ]);

    app(OrdenService::class)->aprobar($orden, $ventas);

    $this->actingAs($responsable);
    $saldo = StockSaldo::query()->sole();
    Livewire::test(ListStockSaldos::class)
        ->assertTableColumnStateSet('cantidad_actual', 20, $saldo)
        ->assertTableColumnStateSet('cantidad_reservada', 6, $saldo)
        ->assertTableColumnStateSet('disponible', 14, $saldo);
    expect($carga->existencias()->sole()->cantidad)->toBe(20);
    $this->assertDatabaseHas('reservas_stock', ['producto_id' => $producto->getKey(), 'cantidad' => 6, 'estado' => ReservaStock::ESTADO_ACTIVA]);
    Livewire::test(ListReservaStocks::class)
        ->assertCanSeeTableRecords(ReservaStock::query()->get())
        ->assertActionHidden(TestAction::make('edit')->table(ReservaStock::query()->sole()));
});

test('rechaza cantidades invalidas antes de guardar una carga', function (int|float $cantidad): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ListPublicacionExistencias::class)
        ->callAction('create', data: [
            'fecha_ingreso' => now()->toDateTimeString(),
            'existencias' => ['linea' => [
                'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(),
                'cantidad' => $cantidad, 'precio_unitario' => 10,
            ]],
        ])
        ->assertHasActionErrors(['existencias.linea.cantidad']);

    $this->assertDatabaseCount('publicaciones_existencias', 0);
})->with(['negativa' => -1, 'fraccionaria' => 1.5]);

test('no confirma una carga si el producto o almacen fue desactivado', function (string $modelo): void {
    $usuario = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $carga = cargaInicialInventario($usuario, [[
        'producto_id' => $producto->getKey(), 'almacen_id' => $almacen->getKey(), 'cantidad' => 3, 'precio_unitario' => 2,
    ]]);
    ($modelo === 'producto' ? $producto : $almacen)->update(['is_active' => false]);

    expect(fn () => app(PublicacionExistenciaService::class)->registrarCargaInicial($carga, $usuario))
        ->toThrow(DomainException::class, 'no está activo');

    $this->assertDatabaseCount('stock_saldos', 0);
    expect($carga->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_BORRADOR);
})->with(['producto', 'almacen']);
