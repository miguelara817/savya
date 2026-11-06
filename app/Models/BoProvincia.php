<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoProvincia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bo_provincias';

    protected $primaryKey = 'bo_provincias_id';

    protected $fillable = [
        'nombre',
        'latitud',
        'longitud',
        'bo_departamentos_id',
    ];

    public function boDepartamento(): BelongsTo
    {
        return $this->belongsTo(BoDepartamento::class, 'bo_departamentos_id');
    }

    public function boMunicipios(): HasMany
    {
        return $this->hasMany(BoMunicipio::class, 'bo_provincias_id');
    }
}
