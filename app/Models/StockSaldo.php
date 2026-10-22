<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'producto_id',
    'almacen_id',
    'cantidad_actual',
    'cantidad_reservada',
])]
class StockSaldo extends Model
{
    public const CREATED_AT = null;

    protected $table = 'stock_saldos';

    protected $primaryKey = 'stock_saldo_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_actual' => 'integer',
            'cantidad_reservada' => 'integer',
        ];
    }

    /**
     * Se calcula como el saldo actual menos las unidades reservadas.
     *
     * @return Attribute<int, never>
     */
    protected function disponible(): Attribute
    {
        return Attribute::get(
            fn (): int => $this->cantidad_actual - $this->cantidad_reservada
        );
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
