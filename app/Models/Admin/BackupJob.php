<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\Staff\Staff;

class BackupJob extends Model
{
    protected $table = 'pool_schema.backup_jobs';

    protected $fillable = [
        'backup_type',
        'file_name',
        'file_size',
        'storage_location',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'triggered_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'triggered_by', 'staff_id');
    }
}
