<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $table = 'pool_schema.access_logs';
    protected $primaryKey = 'log_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'badge_uid',
        'member_id',
        'access_time',
        'access_decision',
        'denial_reason',
        'activity_id',
        'subscription_id',
        'slot_id',
        'staff_id',
    ];

    protected $casts = [
        'access_time'     => 'datetime',
        'access_decision' => 'string',
        'member_id'       => 'integer',
        'staff_id'        => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(\App\Models\Member\Member::class, 'member_id', 'member_id');
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'staff_id', 'staff_id');
    }

    public function activity()
    {
        return $this->belongsTo(\App\Models\Activity\Activity::class, 'activity_id', 'activity_id');
    }

    public function subscription()
    {
        return $this->belongsTo(\App\Models\Finance\Subscription::class, 'subscription_id', 'subscription_id');
    }

    public function slot()
    {
        return $this->belongsTo(\App\Models\Activity\TimeSlot::class, 'slot_id', 'slot_id');
    }
}
