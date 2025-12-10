<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pool\StoreDailyTaskRequest;
use App\Http\Requests\Pool\StoreWeeklyTaskRequest;
use App\Http\Requests\Pool\StoreMonthlyTaskRequest;
use App\Models\Pool\PoolDailyTask;
use App\Models\Pool\PoolWeeklyTask;
use App\Models\Pool\PoolMonthlyTask;
use App\Models\Pool\TaskTemplate;
use App\Models\Pool\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index()
    {
        $pools = Facility::where('active', true)->get();

        // Get active templates
        $dailyTemplate = TaskTemplate::where('type', 'daily')->where('is_active', true)->latest()->first();
        $weeklyTemplate = TaskTemplate::where('type', 'weekly')->where('is_active', true)->latest()->first();
        $monthlyTemplate = TaskTemplate::where('type', 'monthly')->where('is_active', true)->latest()->first();

        // Get today's daily tasks for these pools
        $dailyTasks = PoolDailyTask::where('task_date', today())
            ->whereIn('pool_id', $pools->pluck('facility_id'))
            ->get()
            ->keyBy('pool_id');

        // Get this week's weekly tasks
        $weeklyTasks = PoolWeeklyTask::where('week_number', now()->weekOfYear)
            ->where('year', now()->year)
            ->whereIn('pool_id', $pools->pluck('facility_id'))
            ->get()
            ->keyBy('pool_id');

        // Get this month's monthly tasks
        $monthlyTasks = PoolMonthlyTask::whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->whereIn('facility_id', $pools->pluck('facility_id'))
            ->get()
            ->keyBy('facility_id');

        return view('pool.tasks.index', compact('pools', 'dailyTasks', 'weeklyTasks', 'monthlyTasks', 'dailyTemplate', 'weeklyTemplate', 'monthlyTemplate'));
    }

    public function storeDaily(Request $request)
    {
        $user = Auth::user();
        
        // Dynamic validation based on template could be added here, 
        // but for now we trust the form submission or validate basic structure.
        
        $data = $request->except(['_token', 'template_id']);
        $templateId = $request->input('template_id');

        // Extract standard fields if they exist in the request (backward compatibility or hybrid approach)
        // However, with custom templates, we should store everything in custom_data
        // But we also have standard columns in the DB.
        // Strategy: Map known keys to DB columns, put everything else in custom_data.
        
        $dbColumns = ['pump_status', 'pressure_reading', 'skimmer_cleaned', 'vacuum_done', 'drains_checked', 'lighting_checked', 'debris_removed', 'drain_covers_inspected', 'clarity_test_passed', 'anomalies_comment'];
        
        $insertData = [
            'technician_id' => $user->staff_id,
            'task_date' => today(),
            'pool_id' => $request->pool_id,
            'template_id' => $templateId,
            'custom_data' => $data // Store everything in custom_data for full record
        ];

        foreach ($dbColumns as $col) {
            if (isset($data[$col])) {
                $insertData[$col] = $data[$col];
            }
        }

        PoolDailyTask::updateOrCreate(
            [
                'pool_id' => $request->pool_id,
                'task_date' => today()
            ],
            $insertData
        );

        return back()->with('success', 'Check-list quotidienne enregistrée.');
    }

    public function storeWeekly(Request $request)
    {
        $data = $request->except(['_token', 'template_id']);
        $templateId = $request->input('template_id');

        $dbColumns = ['backwash_done', 'filter_cleaned', 'brushing_done', 'heater_checked', 'chemical_doser_checked', 'fittings_retightened', 'heater_tested', 'general_inspection_comment'];

        $insertData = [
            'technician_id' => Auth::id(), // Or $user->staff_id if consistent
            'pool_id' => $request->pool_id,
            'week_number' => now()->weekOfYear,
            'year' => now()->year,
            'template_id' => $templateId,
            'custom_data' => $data
        ];

        foreach ($dbColumns as $col) {
            if (isset($data[$col])) {
                $insertData[$col] = $data[$col];
            }
        }

        PoolWeeklyTask::updateOrCreate(
            [
                'pool_id' => $request->pool_id,
                'week_number' => now()->weekOfYear,
                'year' => now()->year
            ],
            $insertData
        );

        return back()->with('success', 'Check-list hebdomadaire enregistrée.');
    }

    public function storeMonthly(Request $request)
    {
        $data = $request->except(['_token', 'template_id']);
        $templateId = $request->input('template_id');

        $dbColumns = ['water_replacement_partial', 'full_system_inspection', 'chemical_dosing_calibration', 'notes'];

        $insertData = [
            'technician_id' => Auth::id(),
            'facility_id' => $request->facility_id,
            'completed_at' => now(),
            'template_id' => $templateId,
            'custom_data' => $data
        ];

        foreach ($dbColumns as $col) {
            if (isset($data[$col])) {
                $insertData[$col] = $data[$col];
            }
        }

        PoolMonthlyTask::create($insertData);

        return back()->with('success', 'Check-list mensuelle enregistrée.');
    }
}
