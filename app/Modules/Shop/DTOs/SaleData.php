<?php

namespace App\Modules\Shop\DTOs;

use Illuminate\Http\Request;

class SaleData
{
    public function __construct(
        public array $items,
        public string $payment_method,
        public ?int $member_id
    ) {}

    public static function fromRequest(Request $request): self
    {
        $items = [];
        foreach ($request->input('cart') as $item) {
            $items[] = new SaleItemData(
                product_id: (int) $item['id'],
                quantity: (int) $item['quantity']
            );
        }

        return new self(
            items: $items,
            payment_method: $request->input('payment_method'),
            member_id: $request->input('member_id') ? (int) $request->input('member_id') : null
        );
    }
}
