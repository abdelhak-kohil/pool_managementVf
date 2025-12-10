<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'pool_schema.activities';
    protected $primaryKey = 'activity_id';
    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'access_type', 'color_code', 'is_active'
    ];

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'activity_id', 'activity_id');
    }

    public function plans()
{
    return $this->belongsToMany(\App\Models\Finance\Plan::class, 'pool_schema.activity_plan_prices', 'activity_id', 'plan_id')
                ->withPivot('price');
}

}
