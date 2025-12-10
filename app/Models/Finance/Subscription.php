<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'pool_schema.subscriptions';
    protected $primaryKey = 'subscription_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'activity_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'paused_at',
        'resumes_at',
        'visits_per_week',
        'deactivated_by',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'paused_at'  => 'datetime',
        'resumes_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deactivated_by' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(\App\Models\Member\Member::class, 'member_id', 'member_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'subscription_id', 'subscription_id');
    }

    // Many-to-many to weekdays through subscription_allowed_days
    public function weekdays()
    {
        return $this->belongsToMany(
            \App\Models\Activity\Weekday::class,
            'pool_schema.subscription_allowed_days',
            'subscription_id',
            'weekday_id'
        );
    }

    public function allowedDays()
    {
        return $this->hasMany(SubscriptionAllowedDay::class, 'subscription_id', 'subscription_id')
            ->with('weekday');
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'deactivated_by', 'staff_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'created_by', 'staff_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'updated_by', 'staff_id');
    }


    public function slots()
{
    return $this->hasMany(SubscriptionSlot::class, 'subscription_id');
}

public function activity()
{
    return $this->belongsTo(\App\Models\Activity\Activity::class, 'activity_id', 'activity_id');
}

public function subscriptionslots()
{
    return $this->hasMany(SubscriptionSlot::class, 'subscription_id', 'subscription_id');
}


}




