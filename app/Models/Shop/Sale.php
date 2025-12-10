<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use App\Models\Staff\Staff;

class Sale extends Model
{
    protected $table = 'pool_schema.sales';
    protected $fillable = [
        'staff_id',
        'member_id',
        'total_amount',
        'payment_method'
    ];

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'staff_id', 'staff_id');
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\Member\Member::class, 'member_id', 'member_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
