<?php

namespace App\Models\Pool;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PoolIncident extends Model
{
    protected $table = 'pool_schema.pool_incidents';
    protected $primaryKey = 'incident_id';

    protected $fillable = [
        'title',
        'description',
        'severity',
        'equipment_id',
        'pool_id',
        'created_by',
        'assigned_to',
        'status',
    ];

    public function equipment()
    {
        return $this->belongsTo(PoolEquipment::class, 'equipment_id');
    }

    public function pool()
    {
        return $this->belongsTo(Facility::class, 'pool_id', 'facility_id');
    }

    public function creator()
    {
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Staff::class, 'assigned_to', 'staff_id');
    }
}
