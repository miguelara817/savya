<?php

namespace App\Models;

use Database\Factories\AlmacenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nombre',
    'descripcion',
    'ubicacion',
    'is_active',
])]
class Almacen extends Model
{
    /** @use HasFactory<AlmacenFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'almacenes';

    protected $primaryKey = 'almacen_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Existencia, $this>
     */
    public function existencias(): HasMany
    {
        return $this->hasMany(Existencia::class, 'almacen_id');
    }

    /**
     * @return HasMany<StockSaldo, $this>
     */
    public function stockSaldos(): HasMany
    {
        return $this->hasMany(StockSaldo::class, 'almacen_id');
    }

    /**
     * @return HasMany<ReservaStock, $this>
     */
    public function reservasStock(): HasMany
    {
        return $this->hasMany(ReservaStock::class, 'almacen_id');
    }

    /**
     * @return HasMany<Orden, $this>
     */
    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'almacen_id');
    }

    /**
     * @return HasMany<SalidaAlmacen, $this>
     */
    public function salidas(): HasMany
    {
        return $this->hasMany(SalidaAlmacen::class, 'almacen_id');
    }

    /**
     * @return HasMany<Movimiento, $this>
     */
    public function movimientosOrigen(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'almacen_origen_id');
    }

    /**
     * @return HasMany<Movimiento, $this>
     */
    public function movimientosDestino(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'almacen_destino_id');
    }
}
