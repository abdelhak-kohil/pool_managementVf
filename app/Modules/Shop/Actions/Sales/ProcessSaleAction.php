<?php

namespace App\Modules\Shop\Actions\Sales;

use App\Modules\Shop\DTOs\SaleData;
use App\Models\Shop\Product;
use App\Models\Shop\Sale;
use Illuminate\Support\Facades\DB;
use Exception;

class ProcessSaleAction
{
    public function execute(SaleData $data, int $staffId): Sale
    {
        return DB::transaction(function () use ($data, $staffId) {
            $totalAmount = 0;
            $saleItems = [];

            // 1. Validate & Lock Stock
            foreach ($data->items as $item) {
                // Lock for Update to prevent race conditions
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new Exception("Produit ID {$item->product_id} introuvable.");
                }

                if ($product->stock_quantity < $item->quantity) {
                    throw new Exception("Stock insuffisant pour {$product->name}");
                }

                $subtotal = $product->price * $item->quantity;
                $totalAmount += $subtotal;

                // Decrement Stock
                $product->stock_quantity -= $item->quantity;
                $product->save();

                $saleItems[] = [
                    'product_id' => $product->id, // Assuming 'id' is PK based on controller usage, check model if needed. 
                    // ProductController uses 'Product::create', likely 'id'. 
                    // Wait, previous modules used 'product_id'? No, standard Laravel conventions usually 'id'.
                    // Checking ProductController... 'cart.*.id' => 'exists:products,id'. Yes 'id'.
                    'quantity' => $item->quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // 2. Create Sale Record
            $sale = Sale::create([
                'staff_id' => $staffId,
                'member_id' => $data->member_id,
                'total_amount' => $totalAmount,
                'payment_method' => $data->payment_method,
            ]);

            // 3. Create Sale Items
            foreach ($saleItems as $itemData) {
                $sale->items()->create($itemData);
            }

            return $sale;
        });
    }
}
