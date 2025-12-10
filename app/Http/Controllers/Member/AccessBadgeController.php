<?php 

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\AccessBadge;
use App\Models\Member\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccessBadgeController extends Controller
{
    public function index()
    {
        $badges = AccessBadge::with('member')
            ->orderByDesc('badge_id')
            ->paginate(10);

        return view('badges.index', compact('badges'));
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        $badges = AccessBadge::with('member')
            ->where('badge_uid', 'like', "%{$query}%")
            ->orWhere('status', 'like', "%{$query}%")
            ->orWhereHas('member', function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->orderByDesc('badge_id')
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('badges.partials.table', compact('badges'))->render()
            ]);
        }

        return view('badges.index', compact('badges'));
    }

    public function create()
    {
        $members = Member::doesntHave('accessbadge')->get(); // only those without badge
        return view('badges.create', compact('members'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'badge_uid' => 'required|unique:access_badges,badge_uid',
            'member_id' => 'nullable|exists:members,member_id',
            'status' => 'required|in:active,inactive,lost,revoked,blocked',
        ]);

        AccessBadge::create([
            'badge_uid' => $request->badge_uid,
            'member_id' => $request->member_id,
            'status' => $request->status,
            'issued_at' => now(),
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('badges.index')->with('success', 'Badge créé avec succès.');
    }

    public function edit(AccessBadge $badge)
    {
        $members = Member::doesntHave('accessBadge')
            ->orWhere('member_id', $badge->member_id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('badges.edit', compact('badge', 'members'));
    }

    public function update(Request $request, AccessBadge $badge)
    {
        $request->validate([
            'badge_uid' => "required|unique:access_badges,badge_uid,{$badge->badge_id},badge_id",
            'member_id' => 'nullable|exists:members,member_id',
            'status' => 'required|in:active,inactive,lost,revoked,blocked',
        ]);

        $badge->update($request->only(['member_id', 'badge_uid', 'status', 'expires_at']));

        return redirect()->route('badges.index')->with('success', 'Badge mis à jour avec succès.');
    }

    public function destroy(AccessBadge $badge)
    {
        $badge->delete();
        return redirect()->route('badges.index')->with('success', 'Badge supprimé avec succès.');
    }
}
