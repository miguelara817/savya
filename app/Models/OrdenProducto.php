<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'orden_id',
    'existencia_id',
    'producto_id',
    'cantidad_solicitada',
    'precio_unitario',
])]
class OrdenProducto extends Model
{
    protected $table = 'orden_productos';

    protected $primaryKey = 'orden_producto_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'integer',
            'precio_unitario' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Orden, $this>
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    /**
     * @return BelongsTo<Existencia, $this>
     */
    public function existencia(): BelongsTo
    {
        return $this->belongsTo(Existencia::class, 'existencia_id');
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * @return HasOne<ReservaStock, $this>
     */
    public function reservaStock(): HasOne
    {
        return $this->hasOne(ReservaStock::class, 'orden_producto_id');
    }

    /**
     * @return HasMany<SalidaDetalle, $this>
     */
    public function salidaDetalles(): HasMany
    {
        return $this->hasMany(SalidaDetalle::class, 'orden_producto_id');
    }
}
