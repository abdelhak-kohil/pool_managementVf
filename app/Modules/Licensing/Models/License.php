<?php

namespace App\Modules\Licensing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    protected $table = 'pool_schema.licenses';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'license_key',
        'client_name',
        'email',
        'status',
        'activated_at',
        'last_check_at',
        'server_hash',
        'metadata',
        'module',
    ];

    protected $casts = [
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'last_check_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
