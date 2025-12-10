<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'pool_schema.members';
    protected $primaryKey = 'member_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'date_of_birth',
        'address',
        'created_by',
        'updated_by',
        'photo_path',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'health_conditions',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'created_at'    => 'date',
        'updated_at'    => 'date',
        'created_by'    => 'integer',
        'updated_by'    => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Finance\Subscription::class, 'member_id', 'member_id');
    }

    public function accessBadge()
    {
        return $this->hasOne(AccessBadge::class, 'member_id', 'member_id');
    }

    public function accessLogs()
    {
        return $this->hasMany(\App\Models\Admin\AccessLog::class, 'member_id', 'member_id')->orderByDesc('access_time');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'created_by', 'staff_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'updated_by', 'staff_id');
    }
}
