<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea el usuario administrador y le asigna el rol super_admin.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@correo.bo')],
            [
                'name' => env('ADMIN_NAME', 'admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ],
        );

        if ($admin->email_verified_at === null) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $admin->syncRoles(['super_admin']);
    }
}
