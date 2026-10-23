<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaAutorizada extends Model
{
    protected $table = 'personas_autorizadas';

    protected $primaryKey = 'persona_autorizada_id';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'carnet',
        'correo',
        'telefono',
        'usado',
        'usado_en',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usado' => 'boolean',
            'usado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellido);
    }
}
