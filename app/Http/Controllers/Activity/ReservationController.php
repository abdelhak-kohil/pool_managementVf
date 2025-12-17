<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity\Reservation;
use App\Models\Member\PartnerGroup;
use App\Models\Member\Member;
use App\Models\Activity\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::with(['slot', 'member', 'partnerGroup'])
            ->orderByDesc('reserved_at')
            ->paginate(15);

        return view('reservations.index', compact('reservations'));
    }

    // Define the reservable activity name constant
    const RESERVABLE_ACTIVITY_NAME = 'VIDE';

    public function create()
    {
        $members = DB::table('pool_schema.members')
            ->select('member_id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        $partnerGroups = DB::table('pool_schema.partner_groups')
            ->select('group_id', 'name')
            ->orderBy('name')
            ->get();

        // 🔹 Trouver l’ID de l’activité "VIDE" (ou autre nom configuré)
        $videActivity = DB::table('pool_schema.activities')
            ->whereRaw("LOWER(name) = ?", [strtolower(self::RESERVABLE_ACTIVITY_NAME)])
            ->first();

        if (!$videActivity) {
            return back()->with('error', "⚠️ L'activité '" . self::RESERVABLE_ACTIVITY_NAME . "' n'existe pas dans la base de données.");
        }

        // 🎯 Créneaux disponibles : uniquement ceux avec l’activité “VIDE” et sans réservation
        $availableSlots = DB::table('pool_schema.time_slots as t')
            ->leftJoin('pool_schema.reservations as r', function($join) {
                $join->on('t.slot_id', '=', 'r.slot_id')
                     ->where('r.status', '=', 'confirmed');
            })
            ->join('pool_schema.activities as a', 't.activity_id', '=', 'a.activity_id')
            ->join('pool_schema.weekdays as w', 't.weekday_id', '=', 'w.weekday_id')
            ->where('t.activity_id', $videActivity->activity_id)
            ->whereNull('r.slot_id')
            ->select(
                't.slot_id',
                't.start_time',
                't.end_time',
                'a.name as activity_name',
                't.weekday_id',
                'w.day_name'
            )
            ->orderBy('t.weekday_id')
            ->orderBy('t.start_time')
            ->get();

        return view('reservations.create', compact('members', 'partnerGroups', 'availableSlots'));
    }


    public function store(Request $request, \App\Modules\Operations\Actions\Reservations\CreateReservationAction $action)
    {
        $request->validate([
            'slot_id' => 'required|integer|exists:time_slots,slot_id',
            'reservation_type' => 'required|string|in:member_private,partner_group',
            'member_id' => 'nullable|integer|exists:members,member_id',
            'partner_group_id' => 'nullable|integer|exists:partner_groups,group_id',
            'notes' => 'nullable|string'
        ]);

        try {
            $dto = \App\Modules\Operations\DTOs\ReservationData::fromRequest($request);
            $action->execute($dto);
            
            return redirect()->route('reservations.index')->with('success', '✅ Réservation effectuée avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        } catch (\Throwable $e) {
            return back()->with('error', 'Une erreur est survenue lors de la réservation.');
        }
    }

    public function destroy(Reservation $reservation, \App\Modules\Operations\Actions\Reservations\CancelReservationAction $action)
    {
        try {
            $action->execute($reservation);
            return back()->with('success', 'Réservation annulée.');
        } catch (\Throwable $e) {
             return back()->with('error', 'Erreur lors de l\'annulation.');
        }
    }


    public function searchMembers(Request $request)
    {
        $q = $request->get('q', '');
        $members = Member::where(function($query) use ($q) {
                $query->where(DB::raw("LOWER(CONCAT(first_name, ' ', last_name))"), 'like', '%' . strtolower($q) . '%')
                      ->orWhere('email', 'like', '%' . $q . '%')
                      ->orWhere('phone_number', 'like', '%' . $q . '%')
                      ->orWhere('member_id', 'like', '%' . $q . '%');
            })
            ->select('member_id', DB::raw("CONCAT(first_name, ' ', last_name, ' (', COALESCE(phone_number, 'N/A'), ')') as name"))
            ->limit(10)
            ->get();
        return response()->json($members);
    }

    public function searchGroups(Request $request)
    {
        $q = $request->get('q', '');
        $groups = PartnerGroup::where(function($query) use ($q) {
                $query->where('name', 'ILIKE', "%$q%")
                      ->orWhere('contact_name', 'ILIKE', "%$q%")
                      ->orWhere('contact_phone', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
            })
            ->select('group_id', DB::raw("CONCAT(name, ' (', COALESCE(contact_name, 'N/A'), ')') as name"))
            ->limit(10)
            ->get();
        return response()->json($groups);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        $reservations = Reservation::with(['slot.weekday', 'member', 'partnerGroup'])
            ->where(function ($q) use ($query) {
                $q->whereHas('member', function ($subQ) use ($query) {
                    $subQ->where('first_name', 'like', "%{$query}%")
                         ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhereHas('partnerGroup', function ($subQ) use ($query) {
                    $subQ->where('name', 'like', "%{$query}%");
                });
            })
            ->orderBy('reserved_at', 'desc')
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('reservations.partials.table', compact('reservations'))->render()
            ]);
        }

        return view('reservations.index', compact('reservations'));
    }


}
