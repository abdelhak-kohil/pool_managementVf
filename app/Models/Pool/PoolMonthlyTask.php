<?php

namespace App\Models\Pool;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use App\Models\Pool\Facility;

class PoolMonthlyTask extends Model
{
    protected $table = 'pool_schema.pool_monthly_tasks';
    protected $primaryKey = 'monthly_task_id';
    public $timestamps = false; // Only completed_at

    protected $fillable = [
        'facility_id',
        'technician_id',
        'water_replacement_partial',
        'full_system_inspection',
        'chemical_dosing_calibration',
        'notes',
        'completed_at',
        'custom_data',
        'template_id',
    ];

    protected $casts = [
        'water_replacement_partial' => 'boolean',
        'full_system_inspection' => 'boolean',
        'chemical_dosing_calibration' => 'boolean',
        'completed_at' => 'datetime',
        'custom_data' => 'array',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
