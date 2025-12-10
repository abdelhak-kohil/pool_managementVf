<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'pool_schema.reservations';
    protected $primaryKey = 'reservation_id';
    public $timestamps = false;

    protected $fillable = [
        'slot_id', 'member_id', 'partner_group_id',
        'reservation_type', 'reserved_at', 'status', 'notes'
    ];

    public function slot()
    {
        return $this->belongsTo(TimeSlot::class, 'slot_id', 'slot_id')
            ->with(['activity', 'weekday']);
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\Member\Member::class, 'member_id', 'member_id');
    }

    public function partnerGroup()
    {
        return $this->belongsTo(\App\Models\Member\PartnerGroup::class, 'partner_group_id', 'group_id');
    }
}
