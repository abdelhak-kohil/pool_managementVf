<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Finance\Plan;
use App\Models\Finance\Payment;
use App\Models\Finance\Subscription;
use App\Models\Admin\AccessLog;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $role = strtolower($user->role->role_name ?? '');

        if ($role === 'financer') {
            return redirect()->route('finance.dashboard');
        }

        if ($role === 'receptionniste') {
            return redirect()->route('reception.index');
        }

        // === Basic Stats ===
        $totalMembers = Member::count();
        $activePlans = Plan::where('is_active', true)->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalPayments = Payment::count();

        // === Financial Stats ===
        $totalRevenue = Payment::sum('amount');
        $monthlyRevenue = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // === Subscriptions Breakdown ===
        $statusCounts = Subscription::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // === Access Logs (for chart) ===
        $accessData = AccessLog::select('access_decision', DB::raw('COUNT(*) as total'))
            ->groupBy('access_decision')
            ->pluck('total', 'access_decision');

        // === Recent Access Logs ===
        $recentAccessLogs = AccessLog::with('member')
            ->orderBy('access_time', 'desc')
            ->limit(5)
            ->get();

        // === Revenue Chart (6-month trend) ===
        $revenueChart = Payment::select(
            DB::raw("TO_CHAR(payment_date, 'Mon YYYY') as month"),
            DB::raw("SUM(amount) as total")
        )
        ->groupBy('month')
        ->orderByRaw("MIN(payment_date)")
        ->limit(6)
        ->get();

        return view('admin.dashboard', compact(
            'totalMembers',
            'activePlans',
            'activeSubscriptions',
            'totalPayments',
            'totalRevenue',
            'monthlyRevenue',
            'statusCounts',
            'accessData',
            'recentAccessLogs',
            'revenueChart'
        ));
    }

    public function dashboardStats(Request $request)
    {
        // Basic stats
        $totalMembers = Member::count();
        $activePlans = Plan::where('is_active', true)->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalPayments = Payment::count();

        // Revenue
        $totalRevenue = Payment::sum('amount');
        $monthlyRevenue = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // Subscription status counts
        $statusCounts = Subscription::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Access decision counts
        $accessData = AccessLog::select('access_decision', DB::raw('COUNT(*) as total'))
            ->groupBy('access_decision')
            ->pluck('total', 'access_decision');

        // Recent access logs (5)
        $recentAccessLogs = AccessLog::with('member')
            ->orderBy('access_time', 'desc')
            ->limit(5)
            ->get();

        // Revenue chart 6 months
        $revenueChart = Payment::select(
                DB::raw("TO_CHAR(payment_date, 'Mon YYYY') as month"),
                DB::raw("SUM(amount) as total")
            )
            ->groupBy('month')
            ->orderByRaw("MIN(payment_date)")
            ->limit(6)
            ->get();

        return response()->json([
            'totalMembers' => $totalMembers,
            'activePlans' => $activePlans,
            'activeSubscriptions' => $activeSubscriptions,
            'totalPayments' => $totalPayments,
            'totalRevenue' => (float)$totalRevenue,
            'monthlyRevenue' => (float)$monthlyRevenue,
            'statusCounts' => $statusCounts,
            'accessData' => $accessData,
            'recentAccessLogsHtml' => view('admin.partials.recent-access-rows', ['recentAccessLogs' => $recentAccessLogs])->render(),
            'revenueChart' => $revenueChart,
        ]);
    }
}
