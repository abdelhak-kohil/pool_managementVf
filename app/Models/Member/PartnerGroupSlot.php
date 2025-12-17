<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;

class PartnerGroupSlot extends Model
{
    protected $table = 'pool_schema.partner_group_slots';
    public $timestamps = false; // No created_at/updated_at in definition

    protected $fillable = [
        'partner_group_id',
        'slot_id',
        'max_capacity'
    ];

    public function partnerGroup()
    {
        return $this->belongsTo(PartnerGroup::class, 'partner_group_id', 'group_id');
    }

    public function slot()
    {
        return $this->belongsTo(\App\Models\Activity\TimeSlot::class, 'slot_id', 'slot_id');
    }
}
