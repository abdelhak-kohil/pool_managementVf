<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Shop\Actions\Inventory\CreateProductAction;
use App\Modules\Shop\Actions\Sales\ProcessSaleAction;
use App\Modules\Shop\DTOs\ProductData;
use App\Modules\Shop\DTOs\SaleData;
use App\Modules\Shop\DTOs\SaleItemData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class ShopActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $createProduct;
    protected $processSale;
    protected $staffId;
    protected $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createProduct = app(CreateProductAction::class);
        $this->processSale = app(ProcessSaleAction::class);

        // Ensure Staff exists (minimal fields)
        $this->staffId = DB::table('pool_schema.staff')->insertGetId([
            'first_name' => 'Shop', 
            'last_name' => 'Staff', 
            'email' => 'shop@staff.com',
            'username' => 'shopstaff',
            'phone_number' => '5555555555',
            'password_hash' => 'hash', 
            'salary_type' => 'fixed',
            'hourly_rate' => 0,
            'role_id' => null, 
            'is_active' => true
        ], 'staff_id');

        // Ensure Category exists
        $this->categoryId = DB::table('pool_schema.categories')->insertGetId([
            'name' => 'Drinks',
            'type' => 'product'
        ], 'id');
    }

    public function test_can_create_product()
    {
        $dto = new ProductData(
            category_id: $this->categoryId,
            name: 'Coca Cola',
            description: 'Soda',
            price: 2.50,
            purchase_price: 1.00,
            stock_quantity: 50,
            alert_threshold: 5
        );

        $product = $this->createProduct->execute($dto);

        $this->assertDatabaseHas('pool_schema.products', [
            'name' => 'Coca Cola',
            'stock_quantity' => 50
        ]);
    }

    public function test_process_sale_decrements_stock()
    {
        // 1. Create Product
        $prodId = DB::table('pool_schema.products')->insertGetId([
            'category_id' => $this->categoryId,
            'name' => 'Water',
            'price' => 1.00,
            'purchase_price' => 0.50,
            'stock_quantity' => 10,
            'alert_threshold' => 2
        ], 'id');

        // 2. Prepare Sale Data
        $items = [
            new SaleItemData(product_id: $prodId, quantity: 3)
        ];
        
        $saleDto = new SaleData(
            items: $items,
            payment_method: 'cash',
            member_id: null
        );

        // 3. Process Sale
        $sale = $this->processSale->execute($saleDto, $this->staffId);

        // 4. Verify Stock Decrement
        $this->assertDatabaseHas('pool_schema.products', [
            'id' => $prodId,
            'stock_quantity' => 7 // 10 - 3
        ]);

        // 5. Verify Sale Record
        $this->assertDatabaseHas('pool_schema.sales', [
            'id' => $sale->id,
            'total_amount' => 3.00
        ]);

        // 6. Verify Sale Items
        $this->assertDatabaseHas('pool_schema.sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $prodId,
            'quantity' => 3
        ]);
    }

    public function test_process_sale_fails_insufficient_stock()
    {
        $prodId = DB::table('pool_schema.products')->insertGetId([
            'category_id' => $this->categoryId,
            'name' => 'Rare Item',
            'price' => 100.00,
            'purchase_price' => 50.00,
            'stock_quantity' => 1,
            'alert_threshold' => 0
        ], 'id');

        $items = [
            new SaleItemData(product_id: $prodId, quantity: 5) // Request 5, have 1
        ];

        $saleDto = new SaleData(
            items: $items,
            payment_method: 'card',
            member_id: null
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuffisant');

        $this->processSale->execute($saleDto, $this->staffId);
    }
}
