<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $table = 'pool_schema.backup_settings';

    protected $fillable = [
        'automatic_enabled',
        'scheduled_time',
        'frequency',
        'retention_days',
        'storage_preference',
        'network_path',
    ];

    protected $casts = [
        'automatic_enabled' => 'boolean',
        'retention_days' => 'integer',
    ];
}
