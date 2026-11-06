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
    'registro_emapa',
    'is_active',
    'bo_departamentos_id',
    'bo_provincias_id',
    'bo_municipios_id',
    'bo_localidades_id',
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

    /**
     * @return BelongsTo<BoDepartamento, $this>
     */
    public function boDepartamento(): BelongsTo
    {
        return $this->belongsTo(BoDepartamento::class, 'bo_departamentos_id');
    }

    /**
     * @return BelongsTo<BoProvincia, $this>
     */
    public function boProvincia(): BelongsTo
    {
        return $this->belongsTo(BoProvincia::class, 'bo_provincias_id');
    }

    /**
     * @return BelongsTo<BoMunicipio, $this>
     */
    public function boMunicipio(): BelongsTo
    {
        return $this->belongsTo(BoMunicipio::class, 'bo_municipios_id');
    }

    /**
     * @return BelongsTo<BoLocalidad, $this>
     */
    public function boLocalidad(): BelongsTo
    {
        return $this->belongsTo(BoLocalidad::class, 'bo_localidades_id');
    }
}
