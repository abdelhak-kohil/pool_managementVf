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

public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // 1) Validation de base
        $validated = $request->validate([
            'member_id'    => 'required|exists:members,member_id',
            'activity_id'  => 'required|exists:activities,activity_id',
            'plan_id'      => 'required|exists:plans,plan_id',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|string|in:active,paused,expired,cancelled',
            'slot_ids'     => 'required|array',
            'slot_ids.*'   => 'integer|distinct',
            'amount'       => 'required|numeric|min:0.01',
            'payment_method'=> 'required|string|in:cash,card,transfer',
            'notes'        => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // 2) Récupérer plan et type
        $plan = DB::table('pool_schema.plans')->where('plan_id', $validated['plan_id'])->first();
        if (!$plan) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Plan invalide.');
        }

        $planType = $plan->plan_type;
        $requiredSlotsCount = $planType === 'monthly_weekly' ? (int) $plan->visits_per_week : 1;

        // 🛡️ Strict Date Rules for Monthly Plans
        if ($planType === 'monthly_weekly') {
            $start = \Carbon\Carbon::parse($validated['start_date']);
            $end = \Carbon\Carbon::parse($validated['end_date']);

            // Rule 1: Start date must be the 1st of the month
            if ($start->day !== 1) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Les abonnements mensuels doivent commencer le 1er du mois.');
            }

            // Rule 2: Start date cannot be earlier than today (ignoring time)
            if ($start->startOfDay()->lt(now()->startOfDay())) {
                DB::rollBack();
                return back()->withInput()->with('error', 'La date de début ne peut pas être antérieure à aujourd’hui.');
            }

            // Rule 3: End date must be the last day of the same month
            if (!$end->isSameDay($start->copy()->endOfMonth())) {
                DB::rollBack();
                return back()->withInput()->with('error', 'La date de fin doit être le dernier jour du mois pour un abonnement mensuel.');
            }
        }

        // 🛡️ Strict Rules for Per-Visit Plans
        if ($planType === 'per_visit') {
            $start = \Carbon\Carbon::parse($validated['start_date']);
            $end = \Carbon\Carbon::parse($validated['end_date']);
            $today = now()->startOfDay();

            // Rule 1: Dates must be today
            if (!$start->isSameDay($today) || !$end->isSameDay($today)) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Les abonnements à la séance doivent être pour aujourd’hui.');
            }

            // Rule 2: Only 1 slot allowed (already handled by $requiredSlotsCount = 1)

            // Rule 3: Slot must be in the future
            // We need to check the specific slot time against now()
            // The slot validation loop below will handle checking if the slot exists and is not blocked.
            // We'll add a specific check for time inside the loop or here if we fetch slots early.
        }

        // 3) Vérifier que le nombre de slots choisis correspond au plan
        $selectedSlots = $validated['slot_ids'] ?? [];
        if (count($selectedSlots) !== $requiredSlotsCount) {
            DB::rollBack();
            return back()->withInput()->with('error', "Ce plan requiert exactement {$requiredSlotsCount} créneau(x). Vous en avez sélectionné " . count($selectedSlots) . ".");
        }

        // 4) Vérifier que les slots existent, ne sont pas bloqués et ne sont pas déjà réservés (status = 'confirmed')
        $slots = DB::table('pool_schema.time_slots')
            ->whereIn('slot_id', $selectedSlots)
            ->get();

        if ($slots->count() !== count($selectedSlots)) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Un ou plusieurs créneaux sélectionnés sont introuvables.');
        }

        foreach ($slots as $s) {
            if ($s->is_blocked) {
                DB::rollBack();
                return back()->withInput()->with('error', "Le créneau {$s->slot_id} est bloqué et ne peut pas être utilisé.");
            }

            // vérifier réservation confirmée existante
            $existing = DB::table('pool_schema.reservations')
                ->where('slot_id', $s->slot_id)
                ->where('status', 'confirmed')
                ->exists();

            if ($existing) {
                DB::rollBack();
                return back()->withInput()->with('error', "Le créneau {$s->slot_id} est déjà réservé (confirmé).");
            }

            // 🛡️ Per-Visit: Check time is in the future
            if ($planType === 'per_visit') {
                $slotStart = \Carbon\Carbon::parse($s->start_time); // Assuming start_time is H:i:s
                // We need to combine today's date with slot time to compare
                $slotDateTime = now()->setTimeFrom($slotStart);
                
                if ($slotDateTime->lt(now())) {
                     DB::rollBack();
                     return back()->withInput()->with('error', "Le créneau de {$s->start_time} est déjà passé.");
                }
            }
        }

        // 5) Vérifier le prix réel via activity_plan_prices
        $activityPlan = DB::table('pool_schema.activity_plan_prices')
            ->where('plan_id', $validated['plan_id'])
            ->where('activity_id', $validated['activity_id'])
            ->first();

        if (!$activityPlan) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Aucun tarif trouvé pour la combinaison activité/plan sélectionnée.');
        }

        $realPrice = (float) $activityPlan->price;

        if ($validated['amount'] > $realPrice) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Le montant du paiement dépasse le prix réel de l’abonnement (' . number_format($realPrice, 2) . ').');
        }

        // 6) Empêcher plusieurs abonnements "monthly_weekly" actifs qui se chevauchent pour le même membre
        if ($planType === 'monthly_weekly') {
            $overlapExists = DB::table('pool_schema.subscriptions as s')
                ->join('pool_schema.plans as p', 's.plan_id', '=', 'p.plan_id')
                ->where('s.member_id', $validated['member_id'])
                ->where('p.plan_type', 'monthly_weekly')
                ->where('s.status', 'active')
                // condition chevauchement : NOT (existing.end < new.start OR existing.start > new.end)
                ->whereRaw("NOT (s.end_date < ? OR s.start_date > ?)", [$validated['start_date'], $validated['end_date']])
                ->exists();

            if ($overlapExists) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Le membre possède déjà un abonnement mensuel/hebdomadaire actif qui chevauche la période demandée.');
            }
        }

        // 7) Créer l'abonnement (subscriptions)
        $createdBy = $user->user_id ?? $user->staff_id ?? null;
        $subscriptionId = DB::table('pool_schema.subscriptions')->insertGetId([
            'member_id'       => $validated['member_id'],
            'plan_id'         => $validated['plan_id'],
            'activity_id'     => $validated['activity_id'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'status'          => $validated['status'],
            'visits_per_week' => $planType === 'monthly_weekly' ? $plan->visits_per_week : null,
            'created_by'      => $createdBy,
            'updated_by'      => $createdBy,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], 'subscription_id');

        // 8) Insérer subscription_slots (nouvelle table) et construire la liste des weekday_ids
        $weekdayIds = [];
        foreach ($slots as $s) {
            DB::table('pool_schema.subscription_slots')->insert([
                'subscription_id' => $subscriptionId,
                'slot_id'         => $s->slot_id,
                'created_at'      => now(),
            ]);

            // collect weekday pour subscription_allowed_days si plan weekly
            $weekdayIds[] = $s->weekday_id;

        }

        // 9) Si plan monthly_weekly -> insérer allowed days basé sur les weekday des slots sélectionnés
        if ($planType === 'monthly_weekly') {
            // dédupliquer weekday ids
            $weekdayIds = array_values(array_unique($weekdayIds));

            // On exige que le nombre de weekdays uniques égale visits_per_week ; sinon on échoue
            if (count($weekdayIds) !== (int)$plan->visits_per_week) {
                DB::rollBack();
                return back()->withInput()->with('error', "Les créneaux choisis ne correspondent pas exactement aux {$plan->visits_per_week} jour(s) requis par le plan.");
            }

            // supprimer d'éventuelles anciennes entrées (sécurité, normalement pas nécessaire)
            DB::table('pool_schema.subscription_allowed_days')
                ->where('subscription_id', $subscriptionId)
                ->delete();

            foreach ($weekdayIds as $wd) {
                DB::table('pool_schema.subscription_allowed_days')->insert([
                    'subscription_id' => $subscriptionId,
                    'weekday_id'      => $wd,
                ]);
            }
        }

        // 10) Créer le premier paiement
        DB::table('pool_schema.payments')->insert([
            'subscription_id'      => $subscriptionId,
            'amount'               => $validated['amount'],
            'payment_method'       => $validated['payment_method'],
            'notes'                => $validated['notes'] ?? null,
            'received_by_staff_id' => $user->staff_id ?? null,
            'payment_date'         => now(),
        ]);

        DB::commit();

        return redirect()->route('subscriptions.index')->with('success', 'Abonnement créé avec succès.');
    } catch (\Throwable $e) {
        DB::rollBack();
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


public function update(Request $request, Subscription $subscription)
{
    try {
        DB::beginTransaction();

        $validated = $request->validate([
            'activity_id'       => 'required|exists:activities,activity_id',
            'plan_id'           => 'required|exists:plans,plan_id',
            'status'            => 'required|string',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',

            
            // Slots
            'slot_id'           => 'required|array',
            'slot_id.*'         => 'integer|exists:time_slots,slot_id',

            // Paiement optionnel
            'new_payment_amount'  => 'nullable|numeric|min:0.01',
            'new_payment_method'  => 'nullable|string|in:cash,card,transfer',
            'new_payment_notes'   => 'nullable|string|max:255',
        ]);

        

        $user = Auth::user();
        
        // === Récupérer details plan ===
        $plan = DB::table('pool_schema.plans')->where('plan_id', $validated['plan_id'])->first();
        if (!$plan) {
                return back()->with('error', "Plan introuvable.")->withInput();
            }
        $requiredVisits = $plan->visits_per_week; // obligatoire, défini dans la table plans

        
             
// === Validate slot count based on plan.visits_per_week
        if ($plan->plan_type === 'monthly_weekly') {
            $selectedSlots = $request->slot_id ?? [];
            if (count($selectedSlots) !== $requiredVisits) {
                return back()->with('error', 
                    "Vous devez sélectionner exactement $requiredVisits créneau(x) pour ce plan."
                )->withInput();
            }
        }

        
       
        $isWeekly = $plan->plan_type === 'monthly_weekly';

        // === Récupérer le prix réel pour activité + plan ===
        $realPrice = DB::table('pool_schema.activity_plan_prices')
            ->where('activity_id', $validated['activity_id'])
            ->where('plan_id', $validated['plan_id'])
            ->value('price');

        if (!$realPrice) {
            DB::rollBack();
            return back()->with('error', 'Aucun prix défini pour cette activité et ce plan.');
        }
        
        // === Prévenir plusieurs abonnements weekly actifs ===
        if ($validated['status'] === 'active') {
                        // ===  🚫 Prevent multiple ACTIVE monthly_weekly overlapping subscriptions  ===
            $hasConflict = DB::table('pool_schema.subscriptions')
                ->join('pool_schema.plans', 'plans.plan_id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.member_id', $subscription->member_id)
                ->where('subscriptions.subscription_id', '!=', $subscription->subscription_id)
                ->where('subscriptions.status', 'active')
                ->where('subscriptions.plan_id', $validated['plan_id']) // corrected here
                ->where('plans.plan_type', 'monthly_weekly')
                ->where(function ($q) use ($validated) {
                    $q->whereNot(function ($q2) use ($validated) {
                        $q2->where('subscriptions.end_date', '<', $validated['start_date'])
                        ->orWhere('subscriptions.start_date', '>', $validated['end_date']);
                    });
                })
                ->exists();
                
            if ($hasConflict) {
                return back()->with('error',
                    "Ce membre possède déjà un abonnement mensuel/hebdo actif qui chevauche les dates sélectionnées."
                )->withInput();
            }
        }
        
        // === Valider nombre de slots ===
        $requiredSlots = $isWeekly ? $requiredVisits : 1;

       

        if (count($validated['slot_id']) !== $requiredSlots) {
            DB::rollBack();
            return back()->with('error',
                "Vous devez sélectionner exactement {$requiredSlots} créneau(x) pour cet abonnement."
            );
        }

        

        // === UPDATE abonnement ===
        DB::table('pool_schema.subscriptions')
            ->where('subscription_id', $subscription->subscription_id)
            ->update([
                'activity_id'     => $validated['activity_id'],   // ✅ FIX
                'plan_id'         => $validated['plan_id'],
                'status'          => $validated['status'],
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'visits_per_week' => $isWeekly ? $requiredVisits : null,
                'updated_by'      => $user->user_id ?? null,
                'updated_at'      => now(),
            ]);


        
        
        // === UPDATE SLOTS ===
        DB::table('pool_schema.subscription_slots')
            ->where('subscription_id', $subscription->subscription_id)
            ->delete();

        foreach ($validated['slot_id'] as $slot) {
            DB::table('pool_schema.subscription_slots')->insert([
                'subscription_id' => $subscription->subscription_id,
                'slot_id'         => $slot,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $slotsid[] = $slot;
        }

        // =========================================
// UPDATE subscription_allowed_days
// =========================================

// 1️⃣ Supprimer les anciens jours autorisés
DB::table('pool_schema.subscription_allowed_days')
    ->where('subscription_id', $subscription->subscription_id)
    ->delete();

// 2️⃣ Ne gérer allowed_days que pour monthly_weekly
if ($isWeekly) {

    // Les créneaux sélectionnés
    $selectedSlots = $slotsid;

    if (!empty($selectedSlots)) {

        // 3️⃣ Récupérer les weekday_id correspondants aux slots
        $weekdayIds = DB::table('pool_schema.time_slots')
            ->whereIn('slot_id', $selectedSlots)
            ->pluck('weekday_id')
            ->unique()
            ->values()
            ->toArray();

        // 4️⃣ Vérifier que le nombre de jours autorisés == visits_per_week
        if (count($weekdayIds) !== (int)$plan->visits_per_week) {
            return back()
                ->with('error', "Vous devez sélectionner exactement {$plan->visits_per_week} jour(s) autorisé(s).")
                ->withInput();
        }

        // 5️⃣ Insérer chaque weekday dans subscription_allowed_days
        foreach ($weekdayIds as $weekdayId) {
            DB::table('pool_schema.subscription_allowed_days')->insert([
                'subscription_id' => $subscription->subscription_id,
                'weekday_id'      => $weekdayId,
            ]);
        }
    }
}

        
        // === ADD optional payment ===
        if ($request->filled('new_payment_amount')) {
            DB::table('pool_schema.payments')->insert([
                'subscription_id'      => $subscription->subscription_id,
                'amount'               => $validated['new_payment_amount'],
                'payment_method'       => $validated['new_payment_method'],
                'notes'                => $validated['new_payment_notes'],
                'received_by_staff_id' => $user->staff_id ?? null,
                'payment_date'         => now(),
            ]);
        }

        DB::commit();

        return redirect()
            ->route('subscriptions.index')
            ->with('success', 'Abonnement mis à jour avec succès.');

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Erreur update abonnement: ' . $e->getMessage());
        return back()->with('error', 'Erreur : ' . $e->getMessage());
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



public function addPaymentAjax(Request $request, $subscriptionId)
{
    try {
        // ✅ Validate request fields
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,transfer',
            'notes' => 'nullable|string',
        ]);

        // ✅ Fetch subscription with related plan and activity
        $subscription = DB::table('pool_schema.subscriptions')->where('subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'Abonnement introuvable.']);
        }

        // ✅ Get linked activity and plan IDs
        $activityId = DB::table('pool_schema.activities')
            ->join('pool_schema.activity_plan_prices as app', 'app.activity_id', '=', 'activities.activity_id')
            ->where('app.plan_id', $subscription->plan_id)
            ->select('activities.activity_id')
            ->first()?->activity_id;

        // ✅ Retrieve the exact activity-plan price
        $activityPlanPrice = DB::table('pool_schema.activity_plan_prices')
            ->where('plan_id', $subscription->plan_id)
            ->where('activity_id', $activityId)
            ->value('price');

        if (!$activityPlanPrice) {
            return response()->json(['success' => false, 'message' => 'Aucun tarif trouvé pour cette activité et ce plan.']);
        }

        // ✅ Calculate totals
        $totalPaid = DB::table('pool_schema.payments')->where('subscription_id', $subscriptionId)->sum('amount');
        $remainingBefore = $activityPlanPrice - $totalPaid;

        // Prevent overpayment
        if ($validated['amount'] > $remainingBefore) {
            return response()->json([
                'success' => false,
                'message' => "Le montant dépasse le solde restant (" . number_format($remainingBefore, 2) . " DZD)."
            ]);
        }

        // ✅ Record the new payment
        $user = Auth::user();
        DB::table('pool_schema.payments')->insert([
            'subscription_id' => $subscriptionId,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'received_by_staff_id' => $user->staff_id ?? null,
            'payment_date' => now(),
        ]);

        // ✅ Recalculate new totals
        $totalPaid += $validated['amount'];
        $remaining = max(0, $activityPlanPrice - $totalPaid);
        $progress = $activityPlanPrice > 0 ? round(($totalPaid / $activityPlanPrice) * 100, 1) : 0;

        // ✅ Build JSON response for UI update
        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès.',
            'summary' => [
                'planPrice' => number_format($activityPlanPrice, 2),
                'totalPaid' => number_format($totalPaid, 2),
                'remaining' => number_format($remaining, 2),
                'progress' => $progress,
            ],
            'newPayment' => [
                'date' => now()->format('d/m/Y H:i'),
                'amount' => number_format($validated['amount'], 2),
                'method' => ucfirst($validated['payment_method']),
                'staff' => $user->first_name ?? 'N/A',
            ],
        ]);
    } catch (\Throwable $e) {
        Log::error('Erreur ajout paiement AJAX (real price check): ' . $e->getMessage(), [
            'subscription_id' => $subscriptionId,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['success' => false, 'message' => 'Erreur lors de l’ajout du paiement.']);
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
