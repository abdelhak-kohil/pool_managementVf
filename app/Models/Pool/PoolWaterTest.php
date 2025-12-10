<?php

namespace App\Models\Pool;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PoolWaterTest extends Model
{
    protected $table = 'pool_schema.pool_water_tests';

    protected $fillable = [
        'test_date',
        'technician_id',
        'pool_id',
        'ph',
        'chlorine_free',
        'chlorine_total',
        'bromine',
        'alkalinity',
        'hardness',
        'salinity',
        'turbidity',
        'temperature',
        'orp',
        'comments',
    ];

    protected $casts = [
        'test_date' => 'datetime',
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
