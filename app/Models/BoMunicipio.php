<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoMunicipio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bo_municipios';

    protected $primaryKey = 'bo_municipios_id';

    protected $fillable = [
        'nombre',
        'nombre_oficial',
        'latitud',
        'longitud',
        'bo_provincias_id',
    ];

    public function boProvincia(): BelongsTo
    {
        return $this->belongsTo(BoProvincia::class, 'bo_provincias_id');
    }

    public function boLocalidades(): HasMany
    {
        return $this->hasMany(BoLocalidad::class, 'bo_municipios_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'bo_municipios_id');
    }
}
