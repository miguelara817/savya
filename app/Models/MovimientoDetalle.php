<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'movimiento_id',
    'producto_id',
    'cantidad',
    'precio_unitario',
])]
class MovimientoDetalle extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'movimiento_detalles';

    protected $primaryKey = 'movimiento_detalle_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Movimiento, $this>
     */
    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
