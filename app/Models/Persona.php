<?php

namespace App\Models;

use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'persona_tipo_id',
    'nombre',
    'apellido',
    'carnet',
    'telefono',
    'correo',
    'direccion',
    'is_active',
])]
class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'personas';

    protected $primaryKey = 'persona_id';

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
     * @return BelongsTo<PersonaTipo, $this>
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(PersonaTipo::class, 'persona_tipo_id');
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'persona_id');
    }

    /**
     * @return HasMany<Orden, $this>
     */
    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'persona_id');
    }

    /**
     * @return HasMany<OrdenPersona, $this>
     */
    public function ordenPersonas(): HasMany
    {
        return $this->hasMany(OrdenPersona::class, 'persona_id');
    }

    /**
     * @return HasMany<Entrega, $this>
     */
    public function entregasRecibidas(): HasMany
    {
        return $this->hasMany(Entrega::class, 'receptor_persona_id');
    }
}
