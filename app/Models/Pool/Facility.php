<?php

namespace App\Models\Pool;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Facility extends Model
{
    

    protected $table = 'pool_schema.facilities';
    protected $primaryKey = 'facility_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'capacity',
        'status',
        'type',
        'volume_liters',
        'min_temperature',
        'max_temperature',
        'active',
    ];

    public function waterTests()
    {
        return $this->hasMany(PoolWaterTest::class, 'pool_id', 'facility_id');
    }

    public function incidents()
    {
        return $this->hasMany(PoolIncident::class, 'pool_id', 'facility_id');
    }

    public function dailyTasks()
    {
        return $this->hasMany(PoolDailyTask::class, 'pool_id', 'facility_id');
    }

    public function weeklyTasks()
    {
        return $this->hasMany(PoolWeeklyTask::class, 'pool_id', 'facility_id');
    }
}
