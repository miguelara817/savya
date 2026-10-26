<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersPruebasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $cliente = User::query()->firstOrCreate(
        //     ['email' => 'cliente@correo.bo'],
        //     [
        //         'name' => 'cliente',
        //         'password' => Hash::make('12345678'),
        //     ],
        // );

        // if ($cliente->email_verified_at === null) {
        //     $cliente->forceFill(['email_verified_at' => now()])->save();
        // }

        // $cliente->syncRoles(['cliente']);

        // $rinventario = User::query()->firstOrCreate(
        //     ['email' => 'rinventario@correo.bo'],
        //     [
        //         'name' => 'rinventario',
        //         'password' => Hash::make('12345678'),
        //     ],
        // );

        // if ($rinventario->email_verified_at === null) {
        //     $rinventario->forceFill(['email_verified_at' => now()])->save();
        // }

        // $rinventario->syncRoles(['responsable_inventario']);

        // $ventas = User::query()->firstOrCreate(
        //     ['email' => 'ventas@correo.bo'],
        //     [
        //         'name' => 'ventas',
        //         'password' => Hash::make('12345678'),
        //     ],
        // );

        // if ($ventas->email_verified_at === null) {
        //     $ventas->forceFill(['email_verified_at' => now()])->save();
        // }

        // $ventas->syncRoles(['encargado_ventas']);

        // $almacen = User::query()->firstOrCreate(
        //     ['email' => 'almacen@correo.bo'],
        //     [
        //         'name' => 'almacen',
        //         'password' => Hash::make('12345678'),
        //     ],
        // );

        // if ($almacen->email_verified_at === null) {
        //     $almacen->forceFill(['email_verified_at' => now()])->save();
        // }

        // $almacen->syncRoles(['encargado_almacen']);
    }
}
