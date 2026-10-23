<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'numero',
    'salida_id',
    'estado',
    'fecha_entrega',
    'responsable_user_id',
    'receptor_persona_id',
    'receptor_nombre',
    'receptor_documento',
    'observaciones',
    'firma_receptor_archivo',
    'firmado_en',
    'confirmado_en',
])]
class Entrega extends Model
{
    public const ESTADO_BORRADOR = 'BORRADOR';

    public const ESTADO_CONFIRMADA = 'CONFIRMADA';

    public const ESTADO_ANULADA = 'ANULADA';

    protected $table = 'entregas';

    protected $primaryKey = 'entrega_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'datetime',
            'firmado_en' => 'datetime',
            'confirmado_en' => 'datetime',
        ];
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
     * @return BelongsTo<Persona, $this>
     */
    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'receptor_persona_id');
    }

    /**
     * @return HasMany<EntregaDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(EntregaDetalle::class, 'entrega_id');
    }
}
