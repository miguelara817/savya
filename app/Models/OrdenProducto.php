<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (OrdenProducto $linea): void {
            if ($linea->exists && ! $linea->isDirty(['producto_id', 'existencia_id', 'orden_id'])) {
                $linea->precio_unitario = $linea->getOriginal('precio_unitario');

                return;
            }

            $existencia = Existencia::query()->find($linea->existencia_id);
            $orden = Orden::query()->find($linea->orden_id);

            if ($existencia === null || $orden === null || (int) $existencia->almacen_id !== (int) $orden->almacen_id) {
                throw ValidationException::withMessages(['productos' => 'Selecciona un producto del almacén de la orden.']);
            }

            if ($linea->exists && (int) $linea->getOriginal('producto_id') === (int) $existencia->producto_id
                && (int) $linea->getOriginal('orden_id') === (int) $orden->getKey()) {
                $linea->producto_id = $linea->getOriginal('producto_id');
                $linea->existencia_id = $linea->getOriginal('existencia_id');
                $linea->precio_unitario = $linea->getOriginal('precio_unitario');

                return;
            }

            $saldo = StockSaldo::query()
                ->where('producto_id', $existencia->producto_id)
                ->where('almacen_id', $orden->almacen_id)
                ->whereHas('producto', fn ($query) => $query->where('is_active', true))
                ->whereHas('almacen', fn ($query) => $query->where('is_active', true))
                ->whereHas('existenciaVigente.publicacion', fn ($query) => $query->where('estado', PublicacionExistencia::ESTADO_PUBLICADA))
                ->lockForUpdate()
                ->first();

            if ($saldo === null || $saldo->precio_venta === null) {
                throw ValidationException::withMessages(['productos' => 'El producto no tiene un precio de venta vigente en este almacén.']);
            }

            $linea->producto_id = $saldo->producto_id;
            $linea->existencia_id = $saldo->existencia_vigente_id;
            $linea->precio_unitario = $saldo->precio_venta;
        });
    }

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
