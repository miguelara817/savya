<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoLocalidad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bo_localidades';

    protected $primaryKey = 'bo_localidades_id';

    protected $fillable = [
        'nombre',
        'latitud',
        'longitud',
        'bo_municipios_id',
    ];

    public function boMunicipio(): BelongsTo
    {
        return $this->belongsTo(BoMunicipio::class, 'bo_municipios_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'bo_localidades_id');
    }
}
