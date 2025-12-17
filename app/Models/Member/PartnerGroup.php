<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;

class PartnerGroup extends Model
{
    protected $table = 'pool_schema.partner_groups';
    protected $primaryKey = 'group_id';
    public $timestamps = false;

    protected $fillable = [
        'name', 'contact_name', 'contact_phone', 'email', 'notes'
    ];

    public function reservations()
    {
        return $this->hasMany(\App\Models\Activity\Reservation::class, 'partner_group_id', 'group_id');
    }

    public function badges()
    {
        return $this->hasMany(\App\Models\Member\AccessBadge::class, 'partner_group_id', 'group_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Finance\Subscription::class, 'partner_group_id', 'group_id');
    }

    public function slots()
    {
        return $this->hasMany(\App\Models\Member\PartnerGroupSlot::class, 'partner_group_id', 'group_id');
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Member\PartnerGroupAttendance::class, 'partner_group_id', 'group_id');
    }
}
