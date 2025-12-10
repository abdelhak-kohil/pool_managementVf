<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'pool_schema.staff_attendance';
    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'staff_id',
        'check_in',
        'check_out',
        'date',
        'status',
        'delay_minutes',
        'working_hours',
        'overtime_hours',
        'night_hours',
        'break_minutes',
        'check_in_method',
        'notes',
        'validation_status',
        'validation_date',
        'validated_by',
        'justification',
        'correction_reason',
        'admin_comments',
    ];

    /**
     * Scope a query to only include pending attendances.
     */
    public function scopePending($query)
    {
        return $query->where('validation_status', 'pending');
    }

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'date' => 'date',
        'working_hours' => 'decimal:2',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
}
