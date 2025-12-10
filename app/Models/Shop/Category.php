<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'pool_schema.categories';
    protected $fillable = ['name', 'type'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    //
}
