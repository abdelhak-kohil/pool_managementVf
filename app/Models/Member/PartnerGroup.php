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
}
