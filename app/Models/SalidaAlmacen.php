<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'numero',
    'orden_id',
    'almacen_id',
    'estado',
    'fecha_salida',
    'responsable_user_id',
    'observaciones',
    'firma_responsable_nombre',
    'firma_responsable_archivo',
    'firmado_en',
    'confirmado_en',
])]
class SalidaAlmacen extends Model
{
    public const ESTADO_BORRADOR = 'BORRADOR';

    public const ESTADO_CONFIRMADA = 'CONFIRMADA';

    public const ESTADO_ANULADA = 'ANULADA';

    protected $table = 'salidas_almacen';

    protected $primaryKey = 'salida_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_salida' => 'datetime',
            'firmado_en' => 'datetime',
            'confirmado_en' => 'datetime',
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
     * @return BelongsTo<Almacen, $this>
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    /**
     * @return HasMany<SalidaDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(SalidaDetalle::class, 'salida_id');
    }

    /**
     * @return HasMany<Entrega, $this>
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class, 'salida_id');
    }

    /**
     * @return HasOne<Movimiento, $this>
     */
    public function movimiento(): HasOne
    {
        return $this->hasOne(Movimiento::class, 'salida_id');
    }
}
