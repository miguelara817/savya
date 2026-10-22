<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'orden_id',
    'orden_estado_id',
    'responsable_user_id',
    'observaciones',
])]
class OrdenHistorialEstado extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'orden_historial_estados';

    protected $primaryKey = 'orden_historial_estado_id';

    /**
     * @return BelongsTo<Orden, $this>
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    /**
     * @return BelongsTo<OrdenEstado, $this>
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(OrdenEstado::class, 'orden_estado_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }
}
