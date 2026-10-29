<?php

use App\Enums\OrdenEstadoCodigo;
use App\Models\Almacen;
use App\Models\Existencia;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\OrdenProducto;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\PublicacionExistencia;
use App\Models\User;
use Database\Seeders\OrdenEstadoSeeder;
use Database\Seeders\PersonaTipoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([PersonaTipoSeeder::class, OrdenEstadoSeeder::class, RolesPermisosSeeder::class]);
});

function crearOrdenParaReporte(User $usuario, string $estado = OrdenEstadoCodigo::PendienteAprobacion->value): Orden
{
    $persona = Persona::factory()->create();
    $usuario->forceFill(['persona_id' => $persona->getKey()])->save();
    $almacen = Almacen::factory()->create();

    return Orden::query()->create([
        'codigo_orden' => (string) Str::uuid(),
        'numero' => 'ORD-REPORTE-001',
        'persona_id' => $persona->getKey(),
        'razon_social' => 'Cliente reporte',
        'nit' => '123456789',
        'almacen_id' => $almacen->getKey(),
        'orden_estado_id' => OrdenEstado::query()->where('codigo', $estado)->value('orden_estado_id'),
        'solicitante_user_id' => $usuario->getKey(),
        'fecha_registro' => now(),
    ]);
}

it('lets the owning client print the pending order report with line totals', function (): void {
    $cliente = User::factory()->create();
    $cliente->assignRole('cliente');
    $orden = crearOrdenParaReporte($cliente);
    $producto = Producto::factory()->create(['nombre' => 'Harina', 'presentacion' => 'Bolsa 50 kg']);
    $existencia = Existencia::query()->create([
        'publicacion_id' => PublicacionExistencia::query()->create([
            'numero' => 'PUB-REPORTE-001',
            'fecha_ingreso' => now(),
            'responsable_user_id' => $cliente->getKey(),
            'estado' => PublicacionExistencia::ESTADO_BORRADOR,
        ])->getKey(),
        'producto_id' => $producto->getKey(),
        'almacen_id' => $orden->almacen_id,
        'cantidad' => 500,
        'precio_unitario' => 12.5,
    ]);

    OrdenProducto::query()->createQuietly([
        'orden_id' => $orden->getKey(),
        'existencia_id' => $existencia->getKey(),
        'producto_id' => $producto->getKey(),
        'cantidad_solicitada' => 160,
        'precio_unitario' => 12.5,
    ]);

    $this->actingAs($cliente)
        ->get(route('ordenes.reporte', $orden))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($cliente)
        ->get(route('ordenes.reporte', ['orden' => $orden, 'descargar' => true]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename=orden-ORD-REPORTE-001.pdf');
});

it('does not allow another client to view the order report', function (): void {
    $propietario = User::factory()->create();
    $propietario->assignRole('cliente');
    $orden = crearOrdenParaReporte($propietario);
    $otroCliente = User::factory()->create();
    $otroCliente->assignRole('cliente');

    $this->actingAs($otroCliente)
        ->get(route('ordenes.reporte', $orden))
        ->assertNotFound();
});

it('does not allow report access outside pending approval', function (): void {
    $cliente = User::factory()->create();
    $cliente->assignRole('cliente');
    $orden = crearOrdenParaReporte($cliente, OrdenEstadoCodigo::Aprobada->value);

    $this->actingAs($cliente)
        ->get(route('ordenes.reporte', $orden))
        ->assertNotFound();
});

it('allows a super admin to view a pending order report', function (): void {
    $propietario = User::factory()->create();
    $propietario->assignRole('cliente');
    $orden = crearOrdenParaReporte($propietario);
    $administrador = User::factory()->create();
    $administrador->assignRole('super_admin');

    $this->actingAs($administrador)
        ->get(route('ordenes.reporte', $orden))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
