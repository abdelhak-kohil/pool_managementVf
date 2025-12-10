<?php

namespace App\Models\Pool;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PoolWeeklyTask extends Model
{
    protected $table = 'pool_schema.pool_weekly_tasks';

    protected $fillable = [
        'technician_id',
        'pool_id',
        'week_number',
        'year',
        'backwash_done',
        'filter_cleaned',
        'brushing_done',
        'heater_checked',
        'chemical_doser_checked',
        'fittings_retightened',
        'heater_tested',
        'general_inspection_comment',
        'custom_data',
        'template_id',
    ];

    protected $casts = [
        'backwash_done' => 'boolean',
        'filter_cleaned' => 'boolean',
        'brushing_done' => 'boolean',
        'heater_checked' => 'boolean',
        'chemical_doser_checked' => 'boolean',
        'fittings_retightened' => 'boolean',
        'heater_tested' => 'boolean',
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
