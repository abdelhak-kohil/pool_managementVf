<?php

namespace App\Modules\Shop\DTOs;

use Illuminate\Http\Request;

class ProductData
{
    public function __construct(
        public int $category_id,
        public string $name,
        public ?string $description,
        public float $price,
        public float $purchase_price,
        public int $stock_quantity,
        public int $alert_threshold
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            category_id: (int) $request->input('category_id'),
            name: $request->input('name'),
            description: $request->input('description'),
            price: (float) $request->input('price'),
            purchase_price: (float) $request->input('purchase_price'),
            stock_quantity: (int) $request->input('stock_quantity'),
            alert_threshold: (int) $request->input('alert_threshold')
        );
    }
}
