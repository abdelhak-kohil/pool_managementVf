<?php

namespace App\Models\Staff;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Staff extends Authenticatable
{
    use Notifiable;

    protected $table = 'pool_schema.staff';
    protected $primaryKey = 'staff_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password_hash',
        'role_id',
        'is_active',
        'phone_number',
        'email',
        'specialty',
        'hiring_date',
        'salary_type',
        'hourly_rate',
        'notes',
    ];

    /**
     * Scope a query to only include coaches.
     */
    public function scopeCoaches($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('role_name', 'coach');
        });
    }

    protected $hidden = ['password_hash'];

    // Allow Laravel Auth to use password_hash column
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Mutator - set password (hash)
    // Mutator - set password (hash)
    public function setPasswordHashAttribute($value)
    {
        if ($value && Hash::needsRehash($value)) {
            $this->attributes['password_hash'] = Hash::make($value);
        } else {
            $this->attributes['password_hash'] = $value;
        }
    }

    public function role()
    {
        return $this->belongsTo(\App\Models\Admin\Role::class, 'role_id', 'role_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function hasPermission($permission)
    {
        if ($this->role && strtolower($this->role->role_name) === 'admin') {
            return true;
        }
        return $this->role && $this->role->permissions()->where('permission_name', $permission)->exists();
    }

    public function hasRole($roles)
    {
        if (!$this->role) {
            return false;
        }

        if (is_array($roles)) {
            return in_array(strtolower($this->role->role_name), array_map('strtolower', $roles));
        }

        return strtolower($this->role->role_name) === strtolower($roles);
    }

    public function badges()
    {
        return $this->hasMany(\App\Models\Member\AccessBadge::class, 'staff_id', 'staff_id');
    }

    public function accessLogs()
    {
        return $this->hasMany(\App\Models\Admin\AccessLog::class, 'staff_id', 'staff_id');
    }

    public function latestAccessLog()
    {
        return $this->hasOne(\App\Models\Admin\AccessLog::class, 'staff_id', 'staff_id')->latest('access_time');
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Staff\Attendance::class, 'staff_id', 'staff_id');
    }
}
