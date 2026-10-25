<?php

use App\Filament\Resources\PersonaAutorizadas\Pages\ListPersonaAutorizadas;
use App\Mail\CorreoGenerico;
use App\Models\PersonaAutorizada;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesPermisosSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('enviar credenciales crea el usuario y envia el correo a la persona autorizada', function (): void {
    $administrador = User::factory()->create();
    $administrador->assignRole('administrador');
    $permiso = Permission::query()->firstOrCreate(['name' => 'ViewAny:PersonaAutorizada', 'guard_name' => 'web']);
    $administrador->givePermissionTo($permiso);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $persona = PersonaAutorizada::query()->create([
        'nombre' => 'María',
        'apellido' => 'Pérez',
        'carnet' => '12345678',
        'correo' => 'maria@example.com',
    ]);
    Mail::fake();
    $this->actingAs($administrador);

    livewire(ListPersonaAutorizadas::class)
        ->callAction(TestAction::make('sendCredentials')->table($persona), data: [
            'username' => 'maria@example.com',
            'password' => 'clave-segura-123',
        ])
        ->assertNotified();

    expect($persona->fresh()->user_id)->not->toBeNull()
        ->and($persona->fresh()->usuario->email)->toBe('maria@example.com')
        ->and(password_verify('clave-segura-123', $persona->fresh()->usuario->password))->toBeTrue();

    Mail::assertSent(CorreoGenerico::class, fn (CorreoGenerico $correo): bool => $correo->hasTo('maria@example.com')
        && $correo->hasSubject('Tus credenciales de acceso')
        && str_contains($correo->cuerpo, 'clave-segura-123'));
});
