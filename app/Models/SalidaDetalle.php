<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'salida_id',
    'orden_producto_id',
    'cantidad_despachada',
])]
class SalidaDetalle extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'salida_detalles';

    protected $primaryKey = 'salida_detalle_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_despachada' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SalidaAlmacen, $this>
     */
    public function salida(): BelongsTo
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_id');
    }

    /**
     * @return BelongsTo<OrdenProducto, $this>
     */
    public function ordenProducto(): BelongsTo
    {
        return $this->belongsTo(OrdenProducto::class, 'orden_producto_id');
    }

    /**
     * @return HasMany<EntregaDetalle, $this>
     */
    public function entregaDetalles(): HasMany
    {
        return $this->hasMany(EntregaDetalle::class, 'salida_detalle_id');
    }
}
