<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Exports\AttendanceReportExport;
use App\Exports\PayrollReportExport;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\KpiAssessment;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExecutiveReportController extends Controller
{
    use GuardsHrAccess;

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'report.kpi');
        
        $periods = PayrollPeriod::query()->where('status', '!=', 'draft')->orderByDesc('start_date')->get();
        $selectedPeriod = $request->input('period_code') 
            ? PayrollPeriod::where('code', $request->input('period_code'))->first() 
            : $periods->first();

        $stats = [
            'total_employees' => Employee::query()->where('is_active', true)->count(),
            'active_contracts' => EmploymentContract::query()->where('status', 'aktif')->count(),
            'pending_leaves' => LeaveRequest::query()->whereIn('status', ['menunggu_atasan', 'menunggu_hrd'])->count(),
            'latest_payroll_net' => $selectedPeriod ? (float) $selectedPeriod->runs()->where('status', '!=', 'dibatalkan')->latest('version')->value('total_net') : 0,
            'kpi_avg_score' => KpiAssessment::query()->where('status', 'approved')->avg('final_score') ?? 0,
        ];

        return view('reports.executive', compact('periods', 'selectedPeriod', 'stats'));
    }

    public function attendance(Request $request): View
    {
        $this->ensurePermission($request, 'report.attendance');

        $month = (int) $request->input('month', now('Asia/Jakarta')->month);
        $year = (int) $request->input('year', now('Asia/Jakarta')->year);

        $report = Employee::query()
            ->where('is_active', true)
            ->withCount([
                'attendances as hadir' => fn ($q) => $q->whereMonth('work_date', $month)->whereYear('work_date', $year)->whereIn('status', ['hadir', 'terlambat', 'pulang_awal']),
                'attendances as terlambat' => fn ($q) => $q->whereMonth('work_date', $month)->whereYear('work_date', $year)->where('status', 'terlambat'),
                'attendances as izin' => fn ($q) => $q->whereMonth('work_date', $month)->whereYear('work_date', $year)->whereIn('status', ['izin', 'sakit', 'dispensasi', 'libur']),
                'attendances as alpa' => fn ($q) => $q->whereMonth('work_date', $month)->whereYear('work_date', $year)->where('status', 'tidak_hadir'),
            ])
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('reports.attendance', compact('report', 'month', 'year'));
    }

    public function payroll(Request $request): View
    {
        $this->ensurePermission($request, 'report.payroll');

        $periods = PayrollPeriod::query()->where('status', '!=', 'draft')->orderByDesc('start_date')->get();
        $period = $request->input('period_code')
            ? PayrollPeriod::where('code', $request->input('period_code'))->first()
            : $periods->first();

        $run = $period
            ? $period->runs()->where('status', '!=', 'dibatalkan')->latest('version')->first()
            : null;

        $rows = $run
            ? $run->employees()->with('employee:id,nip,full_name')->orderBy('id')->get()
            : collect();

        return view('reports.payroll', compact('rows', 'period', 'periods', 'run'));
    }

    public function exportAttendance(Request $request): BinaryFileResponse
    {
        $this->ensurePermission($request, 'report.attendance');
        $month = (int) $request->input('month', now('Asia/Jakarta')->month);
        $year = (int) $request->input('year', now('Asia/Jakarta')->year);

        return Excel::download(new AttendanceReportExport($month, $year), "rekap-absensi-{$year}-{$month}.xlsx");
    }

    public function exportPayroll(Request $request): BinaryFileResponse
    {
        $this->ensurePermission($request, 'report.payroll');
        $period = PayrollPeriod::where('code', $request->input('period_code'))->firstOrFail();
        $run = $period->runs()->where('status', '!=', 'dibatalkan')->latest('version')->first();

        return Excel::download(new PayrollReportExport($run), "rekap-payroll-{$period->code}.xlsx");
    }
}
