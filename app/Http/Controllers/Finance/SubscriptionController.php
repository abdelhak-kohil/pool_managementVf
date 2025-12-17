<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Finance\Subscription;
use App\Models\Finance\Plan;
use App\Models\Member\Member;
use App\Models\Activity\Weekday;
use App\Models\Activity\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with([
            'member',
            'plan',
            'activity',
            'allowedDays.weekday',
            'slots.slot.weekday',
            'payments.staff',
            'createdBy',
            'updatedBy',
        ])->orderByDesc('subscription_id');

        // 🔍 Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Member Name
                $q->whereHas('member', function($m) use ($search) {
                    $m->where('first_name', 'ilike', "%{$search}%")
                      ->orWhere('last_name', 'ilike', "%{$search}%");
                })
                // Activity Name
                ->orWhereHas('activity', function($a) use ($search) {
                    $a->where('name', 'ilike', "%{$search}%");
                })
                // Plan Name
                ->orWhereHas('plan', function($p) use ($search) {
                    $p->where('plan_name', 'ilike', "%{$search}%");
                })
                // Status
                ->orWhere('status', 'ilike', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('subscriptions.partials.table', compact('subscriptions'))->render();
        }

        return view('subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $members = Member::orderBy('first_name')->get();
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
        $weekdays = Weekday::orderBy('weekday_id')->get();
        $statuses = ['active', 'paused', 'expired', 'cancelled'];
        $activities=Activity::orderBy('name')->get();
        return view('subscriptions.create', compact('members', 'plans', 'weekdays', 'statuses','activities'));
    }

    public function store(\App\Http\Requests\Finance\StoreSubscriptionRequest $request, \App\Modules\Sales\Actions\CreateSubscriptionAction $action)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null; // Fallback logic

            $data = \App\Modules\Sales\DTOs\SubscriptionData::fromRequest($request, $staffId);

            $action->execute($data);

            return redirect()->route('subscriptions.index')->with('success', 'Abonnement créé avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur création abonnement (store) : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Erreur lors de la création de l’abonnement : ' . $e->getMessage());
        }
    }


public function edit(Subscription $subscription)
{
    try {
        // Relations nécessaires
        $subscription->load([
            'member.accessbadge',
            'plan',
            'activity',
            'payments.staff',
            'allowedDays.weekday',
            'slots.slot.weekday',
            'createdBy',
            'updatedBy',
        ]);

        // Listes pour les selects
        $activities = DB::table('pool_schema.activities')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $plans = DB::table('pool_schema.plans')
         ->where('is_active', true)
         ->orderBy('plan_name')
        ->get();

        $statuses = ['active', 'paused', 'expired', 'cancelled'];

        $weekdays = DB::table('pool_schema.weekdays')
            ->orderBy('weekday_id')
            ->get();

        // Récupérer tous les slots de l'activité de l'abonnement
        $slots = DB::table('pool_schema.time_slots AS t')
            ->join('pool_schema.weekdays AS w', 'w.weekday_id', '=', 't.weekday_id')
            ->where('t.activity_id', $subscription->activity_id)
            ->select(
                't.slot_id',
                't.start_time',
                't.end_time',
                'w.day_name'
            )
            ->orderBy('t.weekday_id')
            ->orderBy('t.start_time')
            ->get();

        // Combien de slots doivent être sélectionnés ?
        $requiredSlots = $subscription->plan->plan_type === 'monthly_weekly'
            ? ($subscription->visits_per_week ?? 1)
            : 1;

        // Prix réel de cet abonnement (activity + plan)
        $planPrice = DB::table('pool_schema.activity_plan_prices')
            ->where('activity_id', $subscription->activity_id)
            ->where('plan_id', $subscription->plan_id)
            ->value('price') ?? 0;

        // Paiements cumulés
        $totalPaid = $subscription->payments->sum('amount');
        $remaining = max(0, $planPrice - $totalPaid);
        $progress = $planPrice > 0 ? round(($totalPaid / $planPrice) * 100, 1) : 0;
        
        return view('subscriptions.edit', compact(
            'subscription',
            'activities',
            'plans',
            'statuses',
            'weekdays',
            'slots',
            'requiredSlots',
            'planPrice',
            'totalPaid',
            'remaining',
            'progress'
        ));

    } catch (\Throwable $e) {
        Log::error('Erreur lors du chargement edit abonnement: ' . $e->getMessage());

        return redirect()
            ->route('subscriptions.index')
            ->with('error', 'Impossible de charger cet abonnement.');
    }
}


    public function update(\App\Http\Requests\Finance\UpdateSubscriptionRequest $request, Subscription $subscription, \App\Modules\Sales\Actions\UpdateSubscriptionAction $action)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;

            $action->execute($subscription->subscription_id, $request->validated(), $staffId);

            return redirect()->route('subscriptions.index')->with('success', 'Abonnement mis à jour avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour abonnement (update) : ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }


        



    public function destroy($id)
    {
        try {
            $subscription = DB::table('pool_schema.subscriptions')->where('subscription_id', $id)->first();

            if (!$subscription) {
                return back()->with('error', 'Abonnement introuvable.');
            }

            // 🛡️ Prevent deletion if payments exist
            $paymentCount = DB::table('pool_schema.payments')->where('subscription_id', $id)->count();
            if ($paymentCount > 0) {
                return back()->with('error', 'Impossible de supprimer cet abonnement car des paiements y sont liés. Veuillez le désactiver à la place.');
            }

            DB::table('pool_schema.subscriptions')
                ->where('subscription_id', $id)
                ->delete();

            return redirect()->route('subscriptions.index')
                             ->with('success', 'Abonnement supprimé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression abonnement: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }



    public function addPaymentAjax(\App\Http\Requests\Finance\StorePaymentRequest $request, $subscriptionId, \App\Modules\Sales\Actions\AddPaymentAction $action)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;

            $result = $action->execute(
                (int)$subscriptionId,
                (float)$request->amount,
                $request->payment_method,
                $request->notes,
                $staffId
            );

            // Adding extra UI formatting to the result here if needed, or rely on Action's return
            $result['newPayment'] = [
                'date' => now()->format('d/m/Y H:i'),
                'amount' => number_format($request->amount, 2),
                'method' => ucfirst($request->payment_method),
                'staff' => $user->first_name ?? 'N/A',
            ];

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
             return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Throwable $e) {
            Log::error('Erreur ajout paiement AJAX (Action): ' . $e->getMessage(), [
                'subscription_id' => $subscriptionId,
                'trace' => $e->getTraceAsString(),
            ]);
            // Extract meaningful message if possible (e.g. from Action constraint)
            $msg = $e instanceof \Exception ? $e->getMessage() : 'Erreur lors de l’ajout du paiement.';
            return response()->json(['success' => false, 'message' => $msg]);
        }
    }



    public function deactivate($id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status === 'active') {
            $subscription->update([
                'status' => 'cancelled',
                'deactivated_by' => Auth::user()->staff_id ?? null,
            ]);
        }

        return redirect()->route('subscriptions.index')->with('success', 'Abonnement désactivé avec succès.');
    }
}
