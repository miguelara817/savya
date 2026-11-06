<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoDepartamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bo_departamentos';

    protected $primaryKey = 'bo_departamentos_id';

    protected $fillable = [
        'nombre',
        'latitud',
        'longitud',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'bo_departamentos_id');
    }

    public function boProvincias(): HasMany
    {
        return $this->hasMany(BoProvincia::class, 'bo_departamentos_id');
    }

    public function boMunicipios(): HasMany
    {
        return $this->hasManyThrough(BoMunicipio::class, BoProvincia::class, 'bo_departamentos_id', 'bo_provincias_id', 'bo_departamentos_id', 'bo_provincias_id');
    }

    public function boLocalidades()
    {
        return BoLocalidad::query()
            ->whereHas('boMunicipio.boProvincia', fn ($query) => $query->where('bo_departamentos.bo_departamentos_id', $this->getKey()));
    }
}
