<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use App\Models\Shop\ProductImage;

class Product extends Model
{
    protected $table = 'pool_schema.products';
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'purchase_price',
        'stock_quantity',
        'alert_threshold',
        'image_path'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
