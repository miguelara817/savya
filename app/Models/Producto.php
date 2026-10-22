<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nombre',
    'slug',
    'codigo',
    'descripcion',
    'unidad_medida',
    'is_active',
])]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $primaryKey = 'producto_id';

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
        return $this->hasMany(Existencia::class, 'producto_id');
    }

    /**
     * @return HasMany<StockSaldo, $this>
     */
    public function stockSaldos(): HasMany
    {
        return $this->hasMany(StockSaldo::class, 'producto_id');
    }

    /**
     * @return HasMany<ReservaStock, $this>
     */
    public function reservasStock(): HasMany
    {
        return $this->hasMany(ReservaStock::class, 'producto_id');
    }

    /**
     * @return HasMany<OrdenProducto, $this>
     */
    public function ordenProductos(): HasMany
    {
        return $this->hasMany(OrdenProducto::class, 'producto_id');
    }

    /**
     * @return HasMany<MovimientoDetalle, $this>
     */
    public function movimientoDetalles(): HasMany
    {
        return $this->hasMany(MovimientoDetalle::class, 'producto_id');
    }
}
