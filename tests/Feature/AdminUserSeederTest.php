<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates the administrator using the configured environment credentials', function (): void {
    config([
        'admin.name' => 'Administrador configurado',
        'admin.email' => 'admin-configurado@example.com',
        'admin.password' => 'secreto-configurado',
    ]);
    Role::findOrCreate('super_admin', 'web');

    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'admin-configurado@example.com')->sole();

    expect($admin->name)->toBe('Administrador configurado')
        ->and(Hash::check('secreto-configurado', $admin->password))->toBeTrue()
        ->and($admin->hasRole('super_admin'))->toBeTrue();
});
