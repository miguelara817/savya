<?php

namespace App\Models;

use Database\Factories\PersonaTipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['valor'])]
class PersonaTipo extends Model
{
    /** @use HasFactory<PersonaTipoFactory> */
    use HasFactory;

    protected $table = 'persona_tipos';

    protected $primaryKey = 'persona_tipo_id';

    /**
     * @return HasMany<Persona, $this>
     */
    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'persona_tipo_id');
    }
}
