<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;

class AccessBadge extends Model
{
    protected $table = 'pool_schema.access_badges';
    protected $primaryKey = 'badge_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'badge_uid',
        'status',
        'issued_at',
        'expires_at',
        'expires_at',
        'staff_id',
    ];

    protected $casts = [
        'issued_at'  => 'datetime',
        'expires_at' => 'datetime',
        'status'     => 'string',
        'member_id'  => 'integer',
        'staff_id'   => 'integer',
    ];

    // Badge belongs to a Member
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    // Badge belongs to a Staff
    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'staff_id', 'staff_id');
    }
}
