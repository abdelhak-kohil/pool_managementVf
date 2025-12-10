<?php

namespace App\Models\Pool;

use Illuminate\Database\Eloquent\Model;

class PoolEquipment extends Model
{
    protected $table = 'pool_schema.pool_equipment';
    protected $primaryKey = 'equipment_id';

    protected $fillable = [
        'name',
        'type',
        'serial_number',
        'location',
        'install_date',
        'status',
        'last_maintenance_date',
        'next_due_date',
        'notes',
    ];

    protected $casts = [
        'install_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_due_date' => 'date',
    ];

    public function maintenanceLogs()
    {
        return $this->hasMany(PoolMaintenance::class, 'equipment_id');
    }

    public function incidents()
    {
        return $this->hasMany(PoolIncident::class, 'equipment_id');
    }
}
