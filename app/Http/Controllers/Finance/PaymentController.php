<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Exports\PaymentsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Finance\Payment;
use App\Models\Finance\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    // === LIST PAYMENTS ===
public function index(Request $request)
{
    $query = Payment::with(['subscription.member', 'staff'])->orderByDesc('payment_date');
    
    // === Filters ===
    if ($request->filled('method') && $request->method != 'all') {
        $query->where('payment_method', $request->method);
    }

    if ($request->filled('plan_id') && $request->plan_id != 'all') {
        $query->whereHas('subscription', function($q) use ($request) {
            $q->where('plan_id', $request->plan_id);
        });
    }

    if ($request->filled('activity_id') && $request->activity_id != 'all') {
        $query->whereHas('subscription', function($q) use ($request) {
            $q->where('activity_id', $request->activity_id);
        });
    }

    if ($request->filled('staff_id') && $request->staff_id != 'all') {
        $query->where('received_by_staff_id', $request->staff_id);
    }

    if ($request->filled('from') && $request->filled('to')) {
        $query->whereBetween('payment_date', [
            $request->from . ' 00:00:00',
            $request->to . ' 23:59:59',
        ]);
    }

    $payments = $query->paginate(10);

    // === Summary Cards (Dynamic) ===
    // Apply same filters to summary stats for consistency (optional, but better UX)
    // For now, keeping global stats as per original code, or we can make them filtered too.
    // Let's keep them global for "Total" context, or filtered? Usually dashboard cards show global.
    // But if user filters by "Cash", they might expect "Total Today" to be "Total Cash Today".
    // Let's stick to the original scope for summary cards for now to avoid complexity, or just use the same query logic if needed.
    // The original code used Payment::where... for summary. Let's leave summary as global for now unless requested.

    $totalToday = Payment::whereDate('payment_date', now())->sum('amount');
    $totalMonth = Payment::where('payment_date', '>=', now()->startOfMonth())->sum('amount');
    $totalAll   = Payment::sum('amount');

    // === Filter Data ===
    $plans = \App\Models\Finance\Plan::select('plan_id', 'plan_name')->get();
    $activities = \App\Models\Activity\Activity::select('activity_id', 'name')->get();
    $staffMembers = \App\Models\Staff\Staff::select('staff_id', 'first_name', 'last_name')->get();

    // === AJAX Support ===
    if ($request->ajax()) {
        $html = view('payments.partials.payments-table', compact('payments'))->render();
        $cards = view('payments.partials.summary-cards', compact('totalToday', 'totalMonth', 'totalAll'))->render();

        return response()->json(['html' => $html, 'cards' => $cards]);
    }

    return view('payments.index', compact('payments', 'totalToday', 'totalMonth', 'totalAll', 'plans', 'activities', 'staffMembers'));
}


    // === CREATE FORM ===public function create()
public function create()
{
    $subscriptions = Subscription::with(['member', 'plan', 'payments'])
        ->get();

    return view('payments.create', compact('subscriptions'));
}


    // === STORE NEW PAYMENT ===
    public function store(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,subscription_id',
            'amount'          => 'required|numeric|min:0',
            'payment_method'  => 'required|in:cash,card,transfer',
            'notes'           => 'nullable|string|max:500'
        ]);

        try {
            Payment::create([
                'subscription_id'     => $request->subscription_id,
                'amount'              => $request->amount,
                'payment_method'      => $request->payment_method,
                'received_by_staff_id'=> Auth::user()->staff_id,
                'notes'               => $request->notes,
                'payment_date'        => now(),
            ]);

            return redirect()->route('payments.index')
                ->with('success', 'Paiement enregistré avec succès ✅');
        } catch (Throwable $e) {
            return back()->with('error', 'Erreur lors de l’enregistrement : ' . $e->getMessage());
        }
    }



// ====   store with Ajax ========
public function storeAjax(Request $request, Subscription $subscription)
{
    try {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Insert new payment
        $paymentId = DB::table('pool_schema.payments')->insertGetId([
            'subscription_id' => $subscription->subscription_id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'received_by_staff_id' => Auth::user()->staff_id ?? 1,
            'payment_date' => now(),
        ],'payment_id');

        // Get inserted payment info
        $newPayment = DB::table('pool_schema.payments')
            ->join('pool_schema.staff', 'pool_schema.staff.staff_id', '=', 'pool_schema.payments.received_by_staff_id')
            ->select('payment_id', 'amount', 'payment_method', 'payment_date', 'staff.first_name as staff_name')
            ->where('payment_id', $paymentId)
            ->first();

        // Recalculate payment summary
        $payments = DB::table('pool_schema.payments')
            ->where('subscription_id', $subscription->subscription_id)
            ->get();

        $totalPaid = $payments->sum('amount');
        $planPrice = DB::table('pool_schema.activity_plan_prices')
            ->where('plan_id', $subscription->plan_id)
            ->where('activity_id', $subscription->activity_id)
            ->value('price') ?? 0;

        $remaining = max(0, $planPrice - $totalPaid);
        $progress = $planPrice > 0 ? round(($totalPaid / $planPrice) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Paiement ajouté avec succès.',
            'payment' => [
                'date' => \Carbon\Carbon::parse($newPayment->payment_date)->format('d/m/Y H:i'),
                'amount' => number_format($newPayment->amount, 2),
                'method' => ucfirst($newPayment->payment_method),
                'staff' => $newPayment->staff_name ?? 'N/A',
            ],
            'summary' => [
                'totalPaid' => number_format($totalPaid, 2),
                'remaining' => number_format($remaining, 2),
                'progress' => $progress,
                'planPrice' => number_format($planPrice, 2),
            ],
        ]);
    } catch (\Throwable $e) {
        Log::error('Erreur paiement AJAX : ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage(),
        ], 500);
    }
}

// =================================

    // === EDIT FORM ===
    public function edit($id)
    {
        
        $payment = Payment::with(['subscription.member', 'staff'])->findOrFail($id);
        // Optimize: Only load the relevant subscription to avoid memory overflow with 5000+ records
        $subscriptions = Subscription::with('member')
            ->where('subscription_id', $payment->subscription_id)
            ->get();
        return view('payments.edit', compact('payment', 'subscriptions'));
    }

    // === UPDATE PAYMENT ===
    public function update(Request $request, $id)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,subscription_id',
            'amount'          => 'required|numeric|min:0',
            'payment_method'  => 'required|in:cash,card,transfer',
            'notes'           => 'nullable|string|max:500'
        ]);

        try {
            $payment = Payment::findOrFail($id);
            $payment->update([
                'subscription_id'     => $request->subscription_id,
                'amount'              => $request->amount,
                'payment_method'      => $request->payment_method,
                'notes'               => $request->notes,
                'received_by_staff_id'=> Auth::user()->staff_id,
                'payment_date'        => now(),
            ]);

            return redirect()->route('payments.index')
                ->with('success', 'Paiement mis à jour avec succès ✅');
        } catch (Throwable $e) {
            return back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    // === DELETE PAYMENT ===
    public function destroy($id)
    {
        try {
            Payment::findOrFail($id)->delete();
            return redirect()->route('payments.index')->with('success', 'Paiement supprimé avec succès 🗑️');
        } catch (Throwable $e) {
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }



    public function exportExcel(Request $request)
{
    $filters = $request->only(['method', 'from', 'to']);
    return Excel::download(new PaymentsExport($filters), 'paiements.xlsx');
}

public function exportPdf(Request $request)
{
    $filters = $request->only(['method', 'from', 'to']);
    $pdf = Pdf::loadView('exports.payments', [
        'payments' => (new PaymentsExport($filters))->view()->getData()['payments'],
        'filters' => $filters
    ]);
    return $pdf->download('paiements.pdf');
}




    public function downloadReceipt($id)
    {
        $payment = Payment::with(['subscription.member', 'subscription.plan', 'subscription.activity', 'staff'])->findOrFail($id);

        $data = [
            'receipt_number' => 'PAY-' . str_pad($payment->payment_id, 6, '0', STR_PAD_LEFT),
            'date' => $payment->payment_date->format('d/m/Y H:i'),
            'customer_name' => $payment->subscription->member ? ($payment->subscription->member->first_name . ' ' . $payment->subscription->member->last_name) : 'Client inconnu',
            'customer_email' => $payment->subscription->member->email ?? '',
            'customer_address' => $payment->subscription->member->address ?? '',
            'payment_method' => $payment->payment_method,
            'staff_name' => $payment->staff->full_name ?? 'Système',
            'items' => [
                [
                    'name' => 'Abonnement : ' . ($payment->subscription->plan->plan_name ?? 'Plan inconnu') . ' - ' . ($payment->subscription->activity->name ?? ''),
                    'quantity' => 1,
                    'unit_price' => $payment->amount,
                    'total' => $payment->amount,
                ]
            ],
            'total' => $payment->amount,
        ];

        if (ob_get_length()) ob_end_clean(); // Clear buffer to prevent corrupt PDF
        $pdf = Pdf::loadView('exports.receipt', $data);
        
        return $pdf->download('recu_paiement_' . $payment->payment_id . '.pdf');
    }

}
