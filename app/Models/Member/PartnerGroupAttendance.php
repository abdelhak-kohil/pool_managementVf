<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;

class PartnerGroupAttendance extends Model
{
    protected $table = 'pool_schema.partner_group_attendance';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;

    protected $fillable = [
        'partner_group_id',
        'badge_id',
        'slot_id',
        'staff_id',
        'attendee_count',
        'access_time',
        'access_decision',
        'denial_reason'
    ];

    protected $casts = [
        'access_time' => 'datetime',
    ];

    public function partnerGroup()
    {
        return $this->belongsTo(PartnerGroup::class, 'partner_group_id', 'group_id');
    }

    public function slot()
    {
        return $this->belongsTo(\App\Models\Activity\TimeSlot::class, 'slot_id', 'slot_id');
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'staff_id', 'staff_id');
    }

    public function badge()
    {
        return $this->belongsTo(AccessBadge::class, 'badge_id', 'badge_id');
    }
}
