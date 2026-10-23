<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'numero',
    'tipo',
    'estado',
    'fecha_movimiento',
    'almacen_origen_id',
    'almacen_destino_id',
    'orden_id',
    'salida_id',
    'responsable_user_id',
    'observaciones',
    'confirmado_en',
])]
class Movimiento extends Model
{
    public const TIPO_ENTRADA = 'ENTRADA';

    public const TIPO_SALIDA = 'SALIDA';

    public const ESTADO_BORRADOR = 'BORRADOR';

    public const ESTADO_CONFIRMADO = 'CONFIRMADO';

    protected $table = 'movimientos';

    protected $primaryKey = 'movimiento_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'datetime',
            'confirmado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Almacen, $this>
     */
    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    /**
     * @return BelongsTo<Almacen, $this>
     */
    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    /**
     * @return BelongsTo<Orden, $this>
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    /**
     * @return BelongsTo<SalidaAlmacen, $this>
     */
    public function salida(): BelongsTo
    {
        return $this->belongsTo(SalidaAlmacen::class, 'salida_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    /**
     * @return HasMany<MovimientoDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(MovimientoDetalle::class, 'movimiento_id');
    }

    /**
     * @return HasMany<MovimientoHistorialEstado, $this>
     */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(MovimientoHistorialEstado::class, 'movimiento_id');
    }
}
