<?php

namespace Database\Seeders;

use App\Models\Almacen;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlmacenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Almacen::updateOrCreate(['nombre' => 'Almacen alquilado Yacuiba', 'ubicacion' => 'Yacuiba']);
        Almacen::updateOrCreate(['nombre' => 'Almacen el Alto', 'ubicacion' => 'El Alto']);
    }
}
