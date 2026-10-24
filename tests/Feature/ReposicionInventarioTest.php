<?php

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Resources\PublicacionExistencias\Pages\ListPublicacionExistencias;
use App\Models\Almacen;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\OrdenProducto;
use App\Models\Producto;
use App\Models\PublicacionExistencia;
use App\Models\StockSaldo;
use App\Models\User;
use App\Services\OrdenService;
use App\Services\PublicacionExistenciaService;
use App\Support\DocumentoNumero;
use Database\Seeders\OrdenEstadoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->seed([RolesPermisosSeeder::class, OrdenEstadoSeeder::class]);
});

/**
 * @param  array<int, array{producto_id: int, almacen_id: int, cantidad: int, precio_unitario: int}>  $lineas
 */
function crearIngresoInventario(User $responsable, array $lineas): PublicacionExistencia
{
    $ingreso = PublicacionExistencia::query()->create([
        'numero' => DocumentoNumero::generar('PUB'),
        'fecha_ingreso' => now(),
        'responsable_user_id' => $responsable->getKey(),
        'estado' => PublicacionExistencia::ESTADO_BORRADOR,
    ]);
    $ingreso->existencias()->createMany($lineas);

    return $ingreso;
}

/**
 * @param  array<int, array{existencia_id: int, producto_id: int, cantidad: int, precio?: int}>  $lineas
 */
function crearOrdenConLineas(User $solicitante, Almacen $almacen, array $lineas): Orden
{
    $orden = Orden::query()->create([
        'codigo_orden' => (string) Str::uuid(),
        'numero' => DocumentoNumero::generar('ORD'),
        'razon_social' => 'Cliente de reposición',
        'nit' => '123456789',
        'almacen_id' => $almacen->getKey(),
        'orden_estado_id' => OrdenEstado::query()
            ->where('codigo', OrdenEstadoCodigo::Borrador->value)
            ->value('orden_estado_id'),
        'solicitante_user_id' => $solicitante->getKey(),
        'fecha_registro' => now(),
    ]);

    foreach ($lineas as $linea) {
        OrdenProducto::query()->create([
            'orden_id' => $orden->getKey(),
            'existencia_id' => $linea['existencia_id'],
            'producto_id' => $linea['producto_id'],
            'cantidad_solicitada' => $linea['cantidad'],
            'precio_unitario' => $linea['precio'] ?? 0,
        ]);
    }

    return $orden->fresh(['productos']);
}

test('al confirmar un ingreso se crea o suma stock y se fija el precio vigente', function (): void {
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();

    $inicial = crearIngresoInventario($responsable, [[
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 20,
        'precio_unitario' => 10,
    ]]);
    app(PublicacionExistenciaService::class)->registrarCargaInicial($inicial, $responsable);

    $saldo = StockSaldo::query()->sole();
    expect($saldo->cantidad_actual)->toBe(20)
        ->and($saldo->precio_venta)->toBe('10.00')
        ->and($saldo->cantidad_reservada)->toBe(0);

    $ingreso = crearIngresoInventario($responsable, [[
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 5,
        'precio_unitario' => 12,
    ]]);
    $existenciaNueva = $ingreso->existencias()->sole();

    app(PublicacionExistenciaService::class)->confirmarIngreso($ingreso, $responsable);

    $saldo->refresh();
    expect($saldo->cantidad_actual)->toBe(25)
        ->and($saldo->precio_venta)->toBe('12.00')
        ->and($saldo->existencia_vigente_id)->toBe($existenciaNueva->getKey())
        ->and($inicial->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_PUBLICADA)
        ->and($ingreso->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_PUBLICADA)
        ->and($inicial->existencias()->sole()->cantidad)->toBe(20);
});

test('las ordenes ya registradas conservan su precio y las nuevas usan el precio vigente', function (): void {
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $solicitante = User::factory()->create()->assignRole('cliente');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();

    $inicial = crearIngresoInventario($responsable, [[
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 20,
        'precio_unitario' => 10,
    ]]);
    app(PublicacionExistenciaService::class)->registrarCargaInicial($inicial, $responsable);
    $existenciaInicial = $inicial->existencias()->sole();

    $orden = crearOrdenConLineas($solicitante, $almacen, [[
        'existencia_id' => $existenciaInicial->getKey(),
        'producto_id' => $producto->getKey(),
        'cantidad' => 2,
        'precio' => 999,
    ]]);
    $linea = $orden->productos()->sole();
    expect($linea->precio_unitario)->toBe('10.00');

    $saldo = StockSaldo::query()->sole();
    $saldo->cantidad_reservada = 4;
    $saldo->save();

    $ingreso = crearIngresoInventario($responsable, [[
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 5,
        'precio_unitario' => 13,
    ]]);
    app(PublicacionExistenciaService::class)->confirmarIngreso($ingreso, $responsable);

    expect($linea->fresh()->precio_unitario)->toBe('10.00')
        ->and($saldo->fresh()->cantidad_reservada)->toBe(4)
        ->and($saldo->fresh()->cantidad_actual)->toBe(25);

    $linea->cantidad_solicitada = 3;
    $linea->save();
    expect($linea->fresh()->precio_unitario)->toBe('10.00');

    $nuevaOrden = crearOrdenConLineas($solicitante, $almacen, [[
        'existencia_id' => $existenciaInicial->getKey(),
        'producto_id' => $producto->getKey(),
        'cantidad' => 1,
    ]]);
    expect($nuevaOrden->productos()->sole()->precio_unitario)->toBe('13.00');
});

test('una orden puede combinar productos de distintas publicaciones', function (): void {
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $solicitante = User::factory()->create()->assignRole('cliente');
    $almacen = Almacen::factory()->create();
    $productoA = Producto::factory()->create();
    $productoB = Producto::factory()->create();

    $cargaA = crearIngresoInventario($responsable, [[
        'producto_id' => $productoA->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 10,
        'precio_unitario' => 4,
    ]]);
    app(PublicacionExistenciaService::class)->registrarCargaInicial($cargaA, $responsable);
    $cargaA->update(['publicada_en' => now()->subDay()]);

    $cargaB = crearIngresoInventario($responsable, [[
        'producto_id' => $productoB->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 8,
        'precio_unitario' => 7,
    ]]);
    app(PublicacionExistenciaService::class)->registrarCargaInicial($cargaB, $responsable);

    $orden = crearOrdenConLineas($solicitante, $almacen, [
        ['existencia_id' => $cargaA->existencias()->sole()->getKey(), 'producto_id' => $productoA->getKey(), 'cantidad' => 2],
        ['existencia_id' => $cargaB->existencias()->sole()->getKey(), 'producto_id' => $productoB->getKey(), 'cantidad' => 3],
    ]);

    $lineas = app(OrdenService::class)->validarOrdenParaAprobacion($orden);

    expect($lineas)->toHaveCount(2)
        ->and($lineas->firstWhere('producto_id', $productoA->getKey())?->precio_unitario)->toBe('4.00')
        ->and($lineas->firstWhere('producto_id', $productoB->getKey())?->precio_unitario)->toBe('7.00');
});

test('las cantidades ingresadas deben ser mayores que cero', function (): void {
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();

    $ingreso = crearIngresoInventario($responsable, [[
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad' => 0,
        'precio_unitario' => 5,
    ]]);

    expect(fn () => app(PublicacionExistenciaService::class)->confirmarIngreso($ingreso, $responsable))
        ->toThrow(DomainException::class, 'mayores que cero');

    $this->assertDatabaseCount('stock_saldos', 0);
    expect($ingreso->fresh()->estado)->toBe(PublicacionExistencia::ESTADO_BORRADOR);
});

test('una orden no puede usar un saldo sin precio vigente', function (): void {
    $solicitante = User::factory()->create()->assignRole('cliente');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    StockSaldo::query()->create([
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad_actual' => 10,
        'cantidad_reservada' => 0,
    ]);

    expect(fn () => crearOrdenConLineas($solicitante, $almacen, [[
        'existencia_id' => 1,
        'producto_id' => $producto->getKey(),
        'cantidad' => 1,
    ]]))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('orden_productos', 0);
});

test('el formulario permite registrar un ingreso en borrador', function (): void {
    $responsable = User::factory()->create()->assignRole('responsable_inventario');
    $producto = Producto::factory()->create();
    $almacen = Almacen::factory()->create();
    $this->actingAs($responsable);

    Livewire::test(ListPublicacionExistencias::class)
        ->callAction('create', data: [
            'fecha_ingreso' => now()->toDateTimeString(),
            'existencias' => [[
                'producto_id' => $producto->getKey(),
                'almacen_id' => $almacen->getKey(),
                'cantidad' => 3,
                'precio_unitario' => 8,
            ]],
        ])
        ->assertHasNoActionErrors();

    $ingreso = PublicacionExistencia::query()->sole();
    expect($ingreso->estado)->toBe(PublicacionExistencia::ESTADO_BORRADOR);
    $this->assertDatabaseCount('stock_saldos', 0);
});
