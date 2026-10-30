<?php

use App\Filament\Resources\UserSessions\Pages\ListUserSessions;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->seed(RolesPermisosSeeder::class);
});

test('administradores pueden consultar sesiones pero otros roles no', function (): void {
    $administrador = User::factory()->create()->assignRole('administrador');
    $sesion = UserSession::query()->create([
        'user_id' => $administrador->getKey(),
        'ip_address' => '192.0.2.10',
        'logged_in_at' => now(),
    ]);

    $this->actingAs($administrador);

    Livewire::test(ListUserSessions::class)
        ->assertCanSeeTableRecords([$sesion])
        ->assertTableColumnStateSet('ip_address', '192.0.2.10', $sesion);

    $this->actingAs(User::factory()->create()->assignRole('cliente'));

    Livewire::test(ListUserSessions::class)->assertForbidden();
});

test('desconocidos no pueden consultar registros de sesiones', function (): void {
    $this->actingAs(User::factory()->create()->assignRole('responsable_inventario'));

    Livewire::test(ListUserSessions::class)->assertForbidden();
});
