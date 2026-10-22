<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codigo',
    'nombre',
    'descripcion',
])]
class OrdenEstado extends Model
{
    public $timestamps = false;

    protected $table = 'orden_estados';

    protected $primaryKey = 'orden_estado_id';

    /**
     * @return HasMany<Orden, $this>
     */
    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'orden_estado_id');
    }

    /**
     * @return HasMany<OrdenHistorialEstado, $this>
     */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(OrdenHistorialEstado::class, 'orden_estado_id');
    }
}
