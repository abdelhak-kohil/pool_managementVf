<?php

namespace App\Models\Pool;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PoolMaintenance extends Model
{
    protected $table = 'pool_schema.pool_equipment_maintenance';
    protected $primaryKey = 'maintenance_id';

    protected $fillable = [
        'equipment_id',
        'technician_id',
        'task_type',
        'status',
        'scheduled_date',
        'completed_date',
        'description',
        'used_parts',
        'working_hours_spent',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function equipment()
    {
        return $this->belongsTo(PoolEquipment::class, 'equipment_id');
    }

    public function technician()
    {
        return $this->belongsTo(Staff::class, 'technician_id', 'staff_id');
    }
}
