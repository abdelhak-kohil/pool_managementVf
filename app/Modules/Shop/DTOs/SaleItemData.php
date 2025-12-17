<?php

namespace App\Modules\Shop\DTOs;

class SaleItemData
{
    public function __construct(
        public int $product_id,
        public int $quantity
    ) {}
}
