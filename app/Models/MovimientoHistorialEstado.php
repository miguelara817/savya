<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'movimiento_id',
    'estado',
    'responsable_user_id',
    'observaciones',
])]
class MovimientoHistorialEstado extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'movimiento_historial_estados';

    protected $primaryKey = 'movimiento_historial_id';

    /**
     * @return BelongsTo<Movimiento, $this>
     */
    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }
}
