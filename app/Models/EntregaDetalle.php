<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'entrega_id',
    'salida_detalle_id',
    'cantidad_entregada',
    'observaciones',
])]
class EntregaDetalle extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'entrega_detalles';

    protected $primaryKey = 'entrega_detalle_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_entregada' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Entrega, $this>
     */
    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class, 'entrega_id');
    }

    /**
     * @return BelongsTo<SalidaDetalle, $this>
     */
    public function salidaDetalle(): BelongsTo
    {
        return $this->belongsTo(SalidaDetalle::class, 'salida_detalle_id');
    }
}
