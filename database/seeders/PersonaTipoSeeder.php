<?php

namespace Database\Seeders;

use App\Models\PersonaTipo;
use Illuminate\Database\Seeder;

class PersonaTipoSeeder extends Seeder
{
    /**
     * Seed the persona types.
     */
    public function run(): void
    {
        $tipos = [
            'Natural',
            'Jurídica',
        ];

        foreach ($tipos as $valor) {
            PersonaTipo::updateOrCreate(['valor' => $valor]);
        }
    }
}
