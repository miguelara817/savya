<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceDataInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'services_data_info';

    protected $fillable = [
        'service',
        'base_url',
        'api_key',
        'session_id',
        'api_key_header',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
