<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'publicacion_id',
    'producto_id',
    'almacen_id',
    'cantidad',
    'precio_unitario',
])]
class Existencia extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'existencias';

    protected $primaryKey = 'existencia_id';

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
     * @return BelongsTo<PublicacionExistencia, $this>
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(PublicacionExistencia::class, 'publicacion_id');
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

    /**
     * @return HasMany<OrdenProducto, $this>
     */
    public function ordenProductos(): HasMany
    {
        return $this->hasMany(OrdenProducto::class, 'existencia_id');
    }
}
