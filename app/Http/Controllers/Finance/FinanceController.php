<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Finance\Payment;
use App\Models\Finance\Subscription;
use App\Models\Finance\Plan;
use App\Models\Activity\Activity;
use App\Models\Finance\Expense;
use App\Models\Shop\Sale; // Added
use App\Models\Shop\SaleItem; // Added

class FinanceController extends Controller
{
    /**
     * Display the finance dashboard view.
     */
    public function dashboard()
    {
        return view('finance.dashboard');
    }

    /**
     * Fetch statistics for the dashboard based on the selected period.
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, year
        $now = Carbon::now();

        if ($period === 'all_years') {
            $startDate = Carbon::create(2020, 1, 1); // Reasonable start date
            $endDate = $now->copy()->endOfYear();
            $groupByFormat = "TO_CHAR(payment_date, 'YYYY')"; // Group by Year
            $expenseGroupByFormat = "TO_CHAR(expense_date, 'YYYY')";
            $groupByLabel = "YYYY";
        } elseif ($period === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
            $groupByFormat = "TO_CHAR(payment_date, 'YYYY-MM')"; // Group by Month
            $expenseGroupByFormat = "TO_CHAR(expense_date, 'YYYY-MM')";
            $groupByLabel = "YYYY-MM";
        } elseif ($period === 'month') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
            $groupByFormat = "DATE(payment_date)"; // Group by Day
            $expenseGroupByFormat = "DATE(expense_date)";
            $groupByLabel = "YYYY-MM-DD";
        } else { // week (default)
            $startDate = $now->copy()->startOfWeek();
            $endDate = $now->copy()->endOfWeek();
            $groupByFormat = "DATE(payment_date)"; // Group by Day
            $expenseGroupByFormat = "DATE(expense_date)";
            $groupByLabel = "YYYY-MM-DD";
        }

        // 1. KPI Cards
        $totalRevenue = Payment::whereBetween('payment_date', [$startDate, $endDate])->sum('amount');
        $subscriptionsSold = Subscription::whereBetween('start_date', [$startDate, $endDate])->count();
        $avgPayment = Payment::whereBetween('payment_date', [$startDate, $endDate])->avg('amount') ?? 0;

        // Growth (vs previous period)
        // Growth (vs previous period)
        $prevStartDate = null;
        $prevEndDate = null;
        
        // Adjust for 'week' which doesn't have a subUnit method like subWeek in generic way easily without switch, 
        // but Carbon has subWeeks, subMonths, subYears.
        if ($period === 'week') {
            $prevStartDate = $startDate->copy()->subWeek();
            $prevEndDate = $endDate->copy()->subWeek();
        } elseif ($period === 'month') {
            $prevStartDate = $startDate->copy()->subMonth();
            $prevEndDate = $endDate->copy()->subMonth();
        } elseif ($period === 'year') {
            $prevStartDate = $startDate->copy()->subYear();
            $prevEndDate = $endDate->copy()->subYear();
        } elseif ($period === 'all_years') {
             // For all years, comparison could be previous 5 years? Or simply 0.
             // Let's just compare to 0 or null logic.
             $prevStartDate = $startDate->copy()->subYears(5); // Arbitrary
             $prevEndDate = $endDate->copy()->subYears(5);
        }

        $prevRevenue = Payment::whereBetween('payment_date', [$prevStartDate, $prevEndDate])->sum('amount');
        $growth = 0;
        if ($prevRevenue > 0) {
            $growth = (($totalRevenue - $prevRevenue) / $prevRevenue) * 100;
        } elseif ($totalRevenue > 0) {
            $growth = 100;
        }

        // 2. Revenue Trend Chart
        $revenueTrend = Payment::select(
                DB::raw("$groupByFormat as date"),
                DB::raw('SUM(amount) as total')
            )
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy(DB::raw($groupByFormat))
            ->orderBy('date')
            ->get();

        // 3. Payment Methods Chart
        $paymentMethods = Payment::select('payment_method', DB::raw('SUM(amount) as total'))
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        // 4. Sales by Activity
        $salesByActivity = Payment::join('pool_schema.subscriptions', 'pool_schema.payments.subscription_id', '=', 'pool_schema.subscriptions.subscription_id')
            ->leftJoin('pool_schema.activities', 'pool_schema.subscriptions.activity_id', '=', 'pool_schema.activities.activity_id')
            ->select(
                DB::raw("COALESCE(pool_schema.activities.name, 'Abonnement Général') as name"), 
                DB::raw('SUM(pool_schema.payments.amount) as total'),
                DB::raw("MAX(pool_schema.activities.activity_id) as activity_id") // For linking
            )
            ->whereBetween('pool_schema.payments.payment_date', [$startDate, $endDate])
            ->groupBy(DB::raw("COALESCE(pool_schema.activities.name, 'Abonnement Général')"))
            ->orderByDesc('total')
            ->get();

        // 5. Revenue by Plan
        $revenueByPlan = Payment::join('pool_schema.subscriptions', 'pool_schema.payments.subscription_id', '=', 'pool_schema.subscriptions.subscription_id')
            ->join('pool_schema.plans', 'pool_schema.subscriptions.plan_id', '=', 'pool_schema.plans.plan_id')
            ->select(
                'pool_schema.plans.plan_name', 
                'pool_schema.plans.plan_id',
                DB::raw('SUM(pool_schema.payments.amount) as total')
            )
            ->whereBetween('pool_schema.payments.payment_date', [$startDate, $endDate])
            ->groupBy('pool_schema.plans.plan_name', 'pool_schema.plans.plan_id')
            ->orderByDesc('total')
            ->get();

        // 6. Top Members
        $topMembers = Payment::join('pool_schema.subscriptions', 'pool_schema.payments.subscription_id', '=', 'pool_schema.subscriptions.subscription_id')
            ->join('pool_schema.members', 'pool_schema.subscriptions.member_id', '=', 'pool_schema.members.member_id')
            ->select(
                'pool_schema.members.member_id',
                'pool_schema.members.first_name', 
                'pool_schema.members.last_name', 
                DB::raw('SUM(pool_schema.payments.amount) as total_paid'),
                DB::raw('COUNT(pool_schema.payments.payment_id) as transaction_count')
            )
            ->whereBetween('pool_schema.payments.payment_date', [$startDate, $endDate])
            ->groupBy('pool_schema.members.member_id', 'pool_schema.members.first_name', 'pool_schema.members.last_name')
            ->orderByDesc('total_paid')
            ->limit(10)
            ->get();

        // 7. Heatmap (Day/Hour)
        $heatmapData = Payment::select(
                DB::raw("EXTRACT(DOW FROM payment_date) as day_of_week"),
                DB::raw("EXTRACT(HOUR FROM payment_date) as hour_of_day"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy(DB::raw("EXTRACT(DOW FROM payment_date)"), DB::raw("EXTRACT(HOUR FROM payment_date)"))
            ->get();

        // === EXPENSES & PROFIT ===
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
        $profit = $totalRevenue - $totalExpenses;

        // Expenses by Category
        $expensesByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('category')
            ->get();

        // Profit Trend (Revenue vs Expenses)
        // We need to merge revenue trend and expense trend
        $expenseTrend = Expense::select(
                DB::raw("$expenseGroupByFormat as date"),
                DB::raw('SUM(amount) as total')
            )
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy(DB::raw($expenseGroupByFormat))
            ->orderBy('date')
            ->get();
        
        // Merge for chart (Frontend can handle merging, but let's provide both arrays)

        // === SHOP STATS ===
        $shopRevenue = Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
        
        // Calculate Shop Profit (Revenue - Cost)
        $shopCost = SaleItem::join('pool_schema.products', 'pool_schema.sale_items.product_id', '=', 'pool_schema.products.id')
            ->join('pool_schema.sales', 'pool_schema.sale_items.sale_id', '=', 'pool_schema.sales.id')
            ->whereBetween('pool_schema.sales.created_at', [$startDate, $endDate])
            ->sum(DB::raw('pool_schema.sale_items.quantity * pool_schema.products.purchase_price'));

        $shopProfit = $shopRevenue - $shopCost;

        // Update Totals
        $globalRevenue = $totalRevenue + $shopRevenue;
        $globalProfit = $globalRevenue - $totalExpenses - $shopCost; // Revenue - Expenses - COGS

        return response()->json([
            'kpi' => [
                'revenue' => $globalRevenue, // Total including shop
                'subscription_revenue' => $totalRevenue,
                'shop_revenue' => $shopRevenue,
                'shop_profit' => $shopProfit,
                'subscriptions' => $subscriptionsSold,
                'avg_payment' => $avgPayment,
                'growth' => $growth,
                'expenses' => $totalExpenses,
                'profit' => $globalProfit
            ],
            'charts' => [
                'trend' => $revenueTrend,
                'expenses_trend' => $expenseTrend,
                'methods' => $paymentMethods,
                'activities' => $salesByActivity,
                'heatmap' => $heatmapData,
                'expenses_by_category' => $expensesByCategory
            ],
            'tables' => [
                'plans' => $revenueByPlan,
                'members' => $topMembers
            ],
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'label' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')
            ]
        ]);
    }
}
