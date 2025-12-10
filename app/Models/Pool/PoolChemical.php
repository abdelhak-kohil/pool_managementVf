<?php

namespace App\Models\Pool;

use Illuminate\Database\Eloquent\Model;

class PoolChemical extends Model
{
    protected $table = 'pool_schema.pool_chemical_stock';
    protected $primaryKey = 'chemical_id';

    protected $fillable = [
        'name',
        'type',
        'quantity_available',
        'unit',
        'minimum_threshold',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function usages()
    {
        return $this->hasMany(PoolChemicalUsage::class, 'chemical_id');
    }
}
