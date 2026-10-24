<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'orden_id',
    'persona_id',
    'nombre',
    'apellido',
    'carnet',
    'correo',
    'telefono',
    'funcion',
])]
class OrdenPersona extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'orden_personas';

    protected $primaryKey = 'orden_persona_id';

    /**
     * @return BelongsTo<Orden, $this>
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
