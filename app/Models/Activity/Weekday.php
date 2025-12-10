<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Weekday extends Model
{
    

    protected $table = 'pool_schema.weekdays';
    protected $primaryKey = 'weekday_id';
    public $timestamps = false;

    protected $fillable = ['weekday_id', 'day_name'];

    public function allowedSubscriptions()
    {
        return $this->hasMany(\App\Models\Finance\SubscriptionAllowedDay::class, 'weekday_id', 'weekday_id');
    }

    /**
     * Relationship: A weekday belongs to many subscriptions.
     */
    public function subscriptions()
    {
        return $this->belongsToMany(
            \App\Models\Finance\Subscription::class,
            'pool_schema.subscription_allowed_days',
            'weekday_id',
            'subscription_id'
        );
    }

     public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'weekday_id', 'weekday_id');
    }
}
