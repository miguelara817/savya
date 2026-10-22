<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'numero',
    'fecha_publicacion',
    'responsable_user_id',
    'estado',
    'observaciones',
    'publicada_en',
])]
class PublicacionExistencia extends Model
{
    protected $table = 'publicaciones_existencias';

    protected $primaryKey = 'publicacion_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'datetime',
            'publicada_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    /**
     * @return HasMany<Existencia, $this>
     */
    public function existencias(): HasMany
    {
        return $this->hasMany(Existencia::class, 'publicacion_id');
    }
}
