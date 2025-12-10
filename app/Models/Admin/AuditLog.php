<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'pool_schema.audit_log';
    protected $primaryKey = 'log_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'changed_by_staff_id',
        'change_timestamp',
        'old_data_jsonb',
        'new_data_jsonb',
    ];

    protected $casts = [
        'old_data_jsonb' => 'array',
        'new_data_jsonb' => 'array',
        'change_timestamp' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'changed_by_staff_id', 'staff_id');
    }
}
