<?php

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AttentionOrdersTable;
use App\Models\Almacen;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\Producto;
use App\Models\StockSaldo;
use App\Models\User;
use Database\Seeders\OrdenEstadoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([OrdenEstadoSeeder::class, RolesPermisosSeeder::class]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function crearOrdenDeDashboard(User $solicitante, Almacen $almacen, string $numero): Orden
{
    return Orden::query()->create([
        'codigo_orden' => (string) Str::uuid(),
        'numero' => $numero,
        'razon_social' => 'Cliente dashboard',
        'nit' => '123456789',
        'almacen_id' => $almacen->getKey(),
        'orden_estado_id' => OrdenEstado::query()->where('codigo', OrdenEstadoCodigo::Aprobada->value)->value('orden_estado_id'),
        'solicitante_user_id' => $solicitante->getKey(),
        'fecha_registro' => now(),
    ]);
}

it('limits client dashboard attention orders to the signed-in client', function (): void {
    $cliente = User::factory()->create();
    $cliente->assignRole('cliente');
    $otroCliente = User::factory()->create();
    $otroCliente->assignRole('cliente');
    $almacen = Almacen::factory()->create();
    $ordenPropia = crearOrdenDeDashboard($cliente, $almacen, 'ORD-DASH-001');
    $ordenAjena = crearOrdenDeDashboard($otroCliente, $almacen, 'ORD-DASH-002');
    $this->actingAs($cliente);

    Livewire::test(AttentionOrdersTable::class)
        ->assertCanSeeTableRecords([$ordenPropia])
        ->assertCanNotSeeTableRecords([$ordenAjena]);
});

it('does not seed obsolete order states', function (): void {
    expect(OrdenEstado::query()
        ->whereIn('codigo', ['PAGO_PENDIENTE', 'ENTREGADA', 'REGISTRADO', 'EFECTIVIZADO', 'ANULADO'])
        ->exists())->toBeFalse()
        ->and(OrdenEstado::query()->where('codigo', OrdenEstadoCodigo::ParcialmenteAtendida->value)->exists())->toBeTrue();
});

it('filters stock dashboard chart by warehouse without ambiguous columns', function (): void {
    $responsable = User::factory()->create();
    $responsable->assignRole('responsable_inventario');
    $almacen = Almacen::factory()->create();
    $producto = Producto::factory()->create();
    StockSaldo::query()->create([
        'producto_id' => $producto->getKey(),
        'almacen_id' => $almacen->getKey(),
        'cantidad_actual' => 20,
        'cantidad_reservada' => 3,
    ]);
    $this->actingAs($responsable);

    Livewire::test(Dashboard::class)
        ->fillForm(['almacen_id' => $almacen->getKey(), 'periodo' => '30_dias'])
        ->assertOk();
});
