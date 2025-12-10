<?php

namespace App\Models\Pool;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PoolDailyTask extends Model
{
    protected $table = 'pool_schema.pool_daily_tasks';
    protected $primaryKey = 'task_id';

    protected $fillable = [
        'technician_id',
        'pool_id',
        'task_date',
        'pump_status',
        'pressure_reading',
        'skimmer_cleaned',
        'vacuum_done',
        'drains_checked',
        'lighting_checked',
        'debris_removed',
        'drain_covers_inspected',
        'clarity_test_passed',
        'anomalies_comment',
        'custom_data',
        'template_id',
    ];

    protected $casts = [
        'task_date' => 'date',
        'skimmer_cleaned' => 'boolean',
        'vacuum_done' => 'boolean',
        'drains_checked' => 'boolean',
        'lighting_checked' => 'boolean',
        'debris_removed' => 'boolean',
        'drain_covers_inspected' => 'boolean',
        'clarity_test_passed' => 'boolean',
        'custom_data' => 'array',
    ];

    public function technician()
    {
        return $this->belongsTo(Staff::class, 'technician_id', 'staff_id');
    }

    public function pool()
    {
        return $this->belongsTo(Facility::class, 'pool_id', 'facility_id');
    }
}
