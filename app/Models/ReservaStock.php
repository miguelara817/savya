<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'orden_producto_id',
    'producto_id',
    'almacen_id',
    'cantidad',
    'estado',
    'reservada_en',
    'liberada_en',
])]
class ReservaStock extends Model
{
    protected $table = 'reservas_stock';

    protected $primaryKey = 'reserva_stock_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'reservada_en' => 'datetime',
            'liberada_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrdenProducto, $this>
     */
    public function ordenProducto(): BelongsTo
    {
        return $this->belongsTo(OrdenProducto::class, 'orden_producto_id');
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * @return BelongsTo<Almacen, $this>
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}
