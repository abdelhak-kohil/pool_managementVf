<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'pool_schema.plans';
    protected $primaryKey = 'plan_id';
    public $timestamps = false;

    protected $fillable = [
        'plan_name',
        'description',

        'plan_type',
        'visits_per_week',
        'duration_months',
        'is_active',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function activities()
{
    return $this->belongsToMany(\App\Models\Activity\Activity::class, 'pool_schema.activity_plan_prices', 'plan_id', 'activity_id')
                ->withPivot('price');
}

}
