<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'pool_schema.sale_items';
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
