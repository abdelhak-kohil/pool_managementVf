<?php

namespace App\Modules\Shop\Actions\Inventory;

use App\Modules\Shop\DTOs\ProductData;
use App\Models\Shop\Product;

class CreateProductAction
{
    public function execute(ProductData $data): Product
    {
        return Product::create([
            'category_id' => $data->category_id,
            'name' => $data->name,
            'description' => $data->description,
            'price' => $data->price,
            'purchase_price' => $data->purchase_price,
            'stock_quantity' => $data->stock_quantity,
            'alert_threshold' => $data->alert_threshold,
        ]);
    }
}
