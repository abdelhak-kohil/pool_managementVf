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
        return $this->getFilteredSubscriptions($request, 'all', 'Tous les Abonnements');
    }

    public function indexMembers(Request $request)
    {
        return $this->getFilteredSubscriptions($request, 'members', 'Abonnements Membres');
    }

    public function indexGroups(Request $request)
    {
        return $this->getFilteredSubscriptions($request, 'groups', 'Abonnements Groupes Partenaires');
    }

    private function getFilteredSubscriptions(Request $request, string $type, string $pageTitle)
    {
        $query = Subscription::with([
            'member',
            'partnerGroup',
            'plan',
            'activity',
            'allowedDays.weekday',
            'slots.slot.weekday',
            'payments.staff',
            'createdBy',
            'updatedBy',
        ])->orderByDesc('subscription_id');

        // 🛡️ Filter by Type
        if ($type === 'members') {
            $query->whereNotNull('member_id');
        } elseif ($type === 'groups') {
            $query->whereNotNull('partner_group_id');
        }

        // 🔍 Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Member Name
                $q->whereHas('member', function($m) use ($search) {
                    $m->where('first_name', 'ilike', "%{$search}%")
                      ->orWhere('last_name', 'ilike', "%{$search}%");
                })
                // Partner Group Name
                ->orWhereHas('partnerGroup', function($pg) use ($search) {
                    $pg->where('name', 'ilike', "%{$search}%");
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
            return view('subscriptions.partials.table', compact('subscriptions', 'type'))->render();
        }

        return view('subscriptions.index', compact('subscriptions', 'pageTitle', 'type'));
    }

    // --- MEMBERS Management ---
    public function createMember()
    {
        $members = Member::orderBy('first_name')->get();
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
        $weekdays = Weekday::orderBy('weekday_id')->get();
        $statuses = ['active', 'paused', 'expired', 'cancelled'];
        $activities = Activity::orderBy('name')->get();
        return view('subscriptions.members.create', compact('members', 'plans', 'weekdays', 'statuses', 'activities'));
    }

    public function storeMember(\App\Http\Requests\Finance\StoreSubscriptionRequest $request, \App\Modules\Sales\Actions\CreateSubscriptionAction $action)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;
            $data = \App\Modules\Sales\DTOs\SubscriptionData::fromRequest($request, $staffId);
            $action->execute($data);

            return redirect()->route('subscriptions.members')->with('success', 'Abonnement membre créé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur storeMember: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // --- PARTNER GROUPS Management ---
    public function createGroup()
    {
        $partnerGroups = \App\Models\Member\PartnerGroup::orderBy('name')->get();
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
        $weekdays = Weekday::orderBy('weekday_id')->get();
        $statuses = ['active', 'paused', 'expired', 'cancelled'];
        $activities = Activity::orderBy('name')->get();
        // Uses the newly created group view
        return view('subscriptions.groups.create', compact('partnerGroups', 'plans', 'weekdays', 'statuses', 'activities'));
    }

    public function storeGroup(\App\Http\Requests\Finance\StoreSubscriptionRequest $request, \App\Modules\Sales\Actions\CreateSubscriptionAction $action)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;
            $data = \App\Modules\Sales\DTOs\SubscriptionData::fromRequest($request, $staffId);
            $action->execute($data);

            return redirect()->route('subscriptions.groups')->with('success', 'Abonnement groupe créé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur storeGroup: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
    
    // Legacy/Generic (Optional, kept for safety or if used elsewhere)
    public function create()
    {
        return $this->createMember(); // Default to member create
    }
    
    public function store(\App\Http\Requests\Finance\StoreSubscriptionRequest $request, \App\Modules\Sales\Actions\CreateSubscriptionAction $action)
    {
        return $this->storeMember($request, $action);
    }


    // --- EDIT / UPDATE MEMBERS ---
    public function editMember(Subscription $subscription)
    {
        return $this->prepareEditView($subscription, 'subscriptions.members.edit');
    }

    public function updateMember(\App\Http\Requests\Finance\UpdateSubscriptionRequest $request, Subscription $subscription, \App\Modules\Sales\Actions\UpdateSubscriptionAction $action)
    {
        return $this->processUpdate($request, $subscription, $action, 'subscriptions.members');
    }

    // --- EDIT / UPDATE GROUPS ---
    public function editGroup(Subscription $subscription)
    {
        return $this->prepareEditView($subscription, 'subscriptions.groups.edit');
    }

    public function updateGroup(\App\Http\Requests\Finance\UpdateSubscriptionRequest $request, Subscription $subscription, \App\Modules\Sales\Actions\UpdateSubscriptionAction $action)
    {
        return $this->processUpdate($request, $subscription, $action, 'subscriptions.groups');
    }
    
    // Helper to avoid duplication in Edit
    private function prepareEditView(Subscription $subscription, string $viewName)
    {
        try {
            $subscription->load([
                'member.accessbadge',
                'partnerGroup',
                'plan',
                'activity',
                'payments.staff',
                'allowedDays.weekday',
                'slots.slot.weekday',
                'createdBy',
                'updatedBy',
            ]);

            $activities = DB::table('pool_schema.activities')->where('is_active', true)->orderBy('name')->get();
            $plans = DB::table('pool_schema.plans')->where('is_active', true)->orderBy('plan_name')->get();
            $statuses = ['active', 'paused', 'expired', 'cancelled'];
            $weekdays = DB::table('pool_schema.weekdays')->orderBy('weekday_id')->get();

            $slots = DB::table('pool_schema.time_slots AS t')
                ->join('pool_schema.weekdays AS w', 'w.weekday_id', '=', 't.weekday_id')
                ->where('t.activity_id', $subscription->activity_id)
                ->select('t.slot_id', 't.start_time', 't.end_time', 'w.day_name')
                ->orderBy('t.weekday_id')->orderBy('t.start_time')
                ->get();

            $requiredSlots = $subscription->plan->plan_type === 'monthly_weekly'
                ? ($subscription->visits_per_week ?? 1)
                : 1;

            $planPrice = DB::table('pool_schema.activity_plan_prices')
                ->where('activity_id', $subscription->activity_id)
                ->where('plan_id', $subscription->plan_id)
                ->value('price') ?? 0;

            $totalPaid = $subscription->payments->sum('amount');
            $remaining = max(0, $planPrice - $totalPaid);
            $progress = $planPrice > 0 ? round(($totalPaid / $planPrice) * 100, 1) : 0;
            
            return view($viewName, compact(
                'subscription', 'activities', 'plans', 'statuses', 'weekdays', 'slots',
                'requiredSlots', 'planPrice', 'totalPaid', 'remaining', 'progress'
            ));

        } catch (\Throwable $e) {
            Log::error('Erreur chargement edit: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Impossible de charger cet abonnement.');
        }
    }

    // Helper for Update
    private function processUpdate($request, $subscription, $action, $redirectRoute)
    {
        try {
            $user = Auth::user();
            $staffId = $user->staff_id ?? $user->user_id ?? null;
            $action->execute($subscription->subscription_id, $request->validated(), $staffId);
            return redirect()->route($redirectRoute)->with('success', 'Abonnement mis à jour avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Erreur update: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // Main Edit (Legacy/Fallback)
    public function edit(Subscription $subscription)
    {
        // Redirect based on type if accessed via generic route? 
        // Or just serve member edit as default.
        if($subscription->partner_group_id) {
            return $this->editGroup($subscription);
        }
        return $this->editMember($subscription);
    }

    public function update(\App\Http\Requests\Finance\UpdateSubscriptionRequest $request, Subscription $subscription, \App\Modules\Sales\Actions\UpdateSubscriptionAction $action)
    {
        if($subscription->partner_group_id) {
            return $this->updateGroup($request, $subscription, $action);
        }
        return $this->updateMember($request, $subscription, $action);
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
