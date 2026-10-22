<?php

namespace App\Models;

use App\PersonaTipo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(
    'persona_tipo',
    'nombre',
    'apellido',
    'carnet',
    'nit',
    'is_active'
)]
class Personas extends Model
{
    Use SoftDeletes;

    protected $table = 'personas';
    
    protected $primaryKey = 'persona_id';

    protected function casts() : array
    {
        return [
            'persona_tipo' => PersonaTipo::class,
            'is_active' => 'boolean',
        ];
    }
}
