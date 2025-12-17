<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $table = 'pool_schema.time_slots';
    protected $primaryKey = 'slot_id';
    public $timestamps = false;

    protected $fillable = [
        'weekday_id', 'start_time', 'end_time', 'activity_id', 
        'assigned_group', 'is_blocked', 'notes', 'capacity', 'coach_id', 'assistant_coach_id', 'created_by'
    ];

    public function assistantCoach()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'assistant_coach_id', 'staff_id');
    }

    public function weekday()
    {
        return $this->belongsTo(Weekday::class, 'weekday_id', 'weekday_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'activity_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'slot_id', 'slot_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'created_by', 'staff_id');
    }

    public function coach()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'coach_id', 'staff_id');
    }

    public function subscriptionSlots()
    {
        return $this->hasMany(\App\Models\Finance\SubscriptionSlot::class, 'slot_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Finance\SubscriptionSlot::class, 'slot_id');
    }

    public function partnerGroups()
    {
        return $this->belongsToMany(\App\Models\Member\PartnerGroup::class, 'pool_schema.partner_group_slots', 'slot_id', 'partner_group_id')
                    ->withPivot(['id', 'max_capacity']);
    }
}
