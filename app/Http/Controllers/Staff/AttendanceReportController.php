<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Attendance;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
// We might need a custom Export class for Excel, but for simplicity we can use a basic collection export or creating a class on the fly if needed,
// but standard practice is a dedicated Export class. For this concise implementation, I will assume we might need to create one.
// Let's create a simple collection export for now or just generic download using a view if Maatwebsite supports 'FromView'.

class AttendanceReportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $staffId = $request->input('staff_id');

        $query = Attendance::with('staff')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'asc');

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendances = $query->get();
        $staffMember = $staffId ? Staff::find($staffId) : null;

        $pdf = Pdf::loadView('staff.attendance.reports.pdf_monthly', compact('attendances', 'month', 'year', 'staffMember'));
        
        return $pdf->download('rapport_presence_' . $month . '_' . $year . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        // For quick implementation without creating a separate Export class file yet, we can try to use a simple approach or just fail over to CSV.
        // Actually, creating an Export class is cleaner. But let's verify if we can do it inline or if we should make `app/Exports/AttendanceExport.php`.
        // I will create a dedicated Export class in the next step. 
        // For now, let's leave this placeholder to be implemented properly.
        return redirect()->back()->with('warning', 'Export Excel en cours de développement.');
    }
}
