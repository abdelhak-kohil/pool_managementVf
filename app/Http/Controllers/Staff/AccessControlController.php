<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Http\Request;

class AccessControlController extends Controller
{
    protected $accessService;

    public function __construct(AccessControlService $accessService)
    {
        $this->accessService = $accessService;
    }

    /**
     * Show the access simulator kiosk.
     */
    public function simulator()
    {
        return view('staff.security.simulator');
    }

    /**
     * Process a badge scan request.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'badge_uid' => 'required|string',
            'location' => 'required|string',
            'action_type' => 'required|string', // entry, exit, door_open, maintenance_start
        ]);

        $result = $this->accessService->verifyAccess(
            $request->badge_uid,
            $request->location,
            $request->action_type
        );

        return response()->json($result);
    }

    /**
     * Display the access logs dashboard.
     */
    public function logs()
    {
        $logs = \Illuminate\Support\Facades\DB::table('pool_schema.access_logs')
            ->leftJoin('pool_schema.staff', 'access_logs.staff_id', '=', 'staff.staff_id')
            ->select('access_logs.*', 'staff.first_name', 'staff.last_name')
            ->orderBy('access_time', 'desc')
            ->paginate(50);

        return view('staff.security.logs', compact('logs'));
    }
}
