<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop\Sale;
use App\Models\Shop\SaleItem;
use App\Models\Shop\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesDashboardController extends Controller
{
    public function index()
    {
        // 1. Total Stats
        $totalRevenue = Sale::sum('total_amount');
        $totalSalesCount = Sale::count();
        
        // Calculate Profit (Revenue - Cost)
        // Cost = Sum(quantity * product.purchase_price)
        $totalCost = SaleItem::join('pool_schema.products', 'pool_schema.sale_items.product_id', '=', 'pool_schema.products.id')
            ->sum(DB::raw('pool_schema.sale_items.quantity * pool_schema.products.purchase_price'));
            
        $totalProfit = $totalRevenue - $totalCost;

        // 2. Monthly Stats (Current Month)
        $startOfMonth = Carbon::now()->startOfMonth();
        $monthlyRevenue = Sale::where('created_at', '>=', $startOfMonth)->sum('total_amount');
        
        $monthlyCost = SaleItem::join('pool_schema.sales', 'pool_schema.sale_items.sale_id', '=', 'pool_schema.sales.id')
            ->join('pool_schema.products', 'pool_schema.sale_items.product_id', '=', 'pool_schema.products.id')
            ->where('pool_schema.sales.created_at', '>=', $startOfMonth)
            ->sum(DB::raw('pool_schema.sale_items.quantity * pool_schema.products.purchase_price'));
            
        $monthlyProfit = $monthlyRevenue - $monthlyCost;

        // 3. Top Selling Products
        $topProducts = SaleItem::select(
                'pool_schema.products.name', 
                DB::raw('SUM(pool_schema.sale_items.quantity) as total_sold'),
                DB::raw('SUM(pool_schema.sale_items.subtotal) as total_revenue')
            )
            ->join('pool_schema.products', 'pool_schema.sale_items.product_id', '=', 'pool_schema.products.id')
            ->groupBy('pool_schema.products.id', 'pool_schema.products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // 4. Recent Sales
        $recentSales = Sale::with(['staff', 'member', 'items.product'])
            ->latest()
            ->limit(10)
            ->get();

        return view('sales.dashboard', compact(
            'totalRevenue', 
            'totalProfit', 
            'totalSalesCount', 
            'monthlyRevenue', 
            'monthlyProfit', 
            'topProducts', 
            'recentSales'
        ));
    }
}
