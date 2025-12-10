<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{
    public function __construct()
    {
       // $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display audit log dashboard with filters and stats.
     */
    public function index(Request $request)
    {
        dd($request);
        $filters = [
            'table' => $request->input('table'),
            'action' => $request->input('action'),
            'user' => $request->input('user'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $query = DB::table('pool_schema.audit_log as al')
            ->leftJoin('pool_schema.staff as s', 'al.changed_by_staff_id', '=', 's.staff_id')
            ->select(
                'al.log_id',
                'al.table_name',
                'al.record_id',
                'al.action',
                's.first_name as user_first_name',
                's.last_name as user_last_name',
                'al.change_timestamp',
                'al.old_data_jsonb',
                'al.new_data_jsonb'
            )
            ->orderByDesc('al.change_timestamp');

        // Apply filters dynamically
        if ($filters['table']) {
            $query->where('al.table_name', 'ILIKE', "%{$filters['table']}%");
        }
        if ($filters['action']) {
            $query->where('al.action', '=', $filters['action']);
        }
        if ($filters['user']) {
            $query->whereRaw("LOWER(CONCAT(s.first_name, ' ', s.last_name)) LIKE ?", ['%' . strtolower($filters['user']) . '%']);
        }
        if ($filters['start_date']) {
            $query->whereDate('al.change_timestamp', '>=', $filters['start_date']);
        }
        if ($filters['end_date']) {
            $query->whereDate('al.change_timestamp', '<=', $filters['end_date']);
        }

        $logs = $query->paginate(10)->appends($filters);

        // Stats
        $stats = [
            'total' => DB::table('pool_schema.audit_log')->count(),
            'this_month' => DB::table('pool_schema.audit_log')
                ->whereRaw("date_part('month', change_timestamp) = date_part('month', now())")
                ->count(),
            'tables' => DB::table('pool_schema.audit_log')
                ->select('table_name', DB::raw('COUNT(*) as count'))
                ->groupBy('table_name')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'actions' => DB::table('pool_schema.audit_log')
                ->select('action', DB::raw('COUNT(*) as count'))
                ->groupBy('action')
                ->get(),
        ];

        return view('admin.audit.dashboard', compact('logs', 'filters', 'stats'));
    }

    /**
     * Fetch log details (AJAX modal)
     */
    public function show($id)
    {
        $log = DB::table('pool_schema.audit_log as al')
            ->leftJoin('pool_schema.staff as s', 'al.changed_by_staff_id', '=', 's.staff_id')
            ->where('al.log_id', $id)
            ->select(
                'al.*',
                's.first_name as staff_first_name',
                's.last_name as staff_last_name'
            )
            ->first();

        if (!$log) {
            return response()->json(['success' => false, 'message' => 'Entrée introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $log]);
    }
}
