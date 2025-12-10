<?php

namespace App\Models\Pool;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PoolChemicalUsage extends Model
{
    protected $table = 'pool_schema.pool_chemical_usage';
    protected $primaryKey = 'usage_id';

    protected $fillable = [
        'chemical_id',
        'technician_id',
        'quantity_used',
        'usage_date',
        'purpose',
        'related_test_id',
        'comments',
    ];

    protected $casts = [
        'usage_date' => 'datetime',
    ];

    public function chemical()
    {
        return $this->belongsTo(PoolChemical::class, 'chemical_id');
    }

    public function technician()
    {
        return $this->belongsTo(Staff::class, 'technician_id', 'staff_id');
    }

    public function relatedTest()
    {
        return $this->belongsTo(PoolWaterTest::class, 'related_test_id');
    }
}
