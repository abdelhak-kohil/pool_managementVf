<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'pool_schema.product_images';
    
    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
