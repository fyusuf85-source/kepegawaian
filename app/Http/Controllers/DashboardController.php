<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmploymentContract;
use App\Models\Holiday;
use App\Models\KpiAssessment;
use App\Models\LeaveRequest;
use App\Models\OrgUnit;
use App\Models\PayrollPeriod;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee;

        $stats = [];

        if ($user->hasPermission('employee.view.all') || $user->hasPermission('employee.view.unit')) {
            $visible = Employee::query()->visibleTo($user);

            $stats['employees_total'] = (clone $visible)->count();
            $stats['employees_active'] = (clone $visible)->where('is_active', true)->count();
            $stats['employees_new'] = (clone $visible)
                ->whereBetween('hire_date', [
                    Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString(),
                    Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString(),
                ])
                ->count();
        }

        if ($user->hasPermission('employee.document.view')) {
            $stats['documents_expiring'] = EmployeeDocument::query()
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [
                    Carbon::now('Asia/Jakarta')->toDateString(),
                    Carbon::now('Asia/Jakarta')->addDays(30)->toDateString(),
                ])
                ->count();
        }

        $stats['org_units'] = OrgUnit::query()->where('is_active', true)->count();

        if ($user->hasAnyPermission(['kpi.view.all', 'kpi.view.unit', 'kpi.view.self'])) {
            $kpiQuery = KpiAssessment::query();
            if (! $user->hasPermission('kpi.view.all')) {
                $kpiQuery->whereIn('employee_id', Employee::query()->visibleTo($user)->select('id'));
            }
            $stats['kpi_count'] = (clone $kpiQuery)->count();
            $stats['kpi_average'] = (clone $kpiQuery)->where('status', 'approved')->avg('final_score') ?? 0;
        }

        if ($user->hasAnyPermission(['contract.view', 'mutation.view'])) {
            $stats['contracts_active'] = EmploymentContract::query()->where('status', 'aktif')->count();
        }

        if ($user->hasAnyPermission(['leave.view.unit', 'leave.view.all', 'leave.approve.hrd', 'leave.approve.supervisor'])) {
            $stats['leave_pending'] = LeaveRequest::query()->whereIn('status', ['menunggu_atasan', 'menunggu_hrd'])->count();
        }

        if ($user->hasAnyPermission(['payroll.view.all', 'payroll.report'])) {
            $latestPeriod = PayrollPeriod::query()->where('status', '!=', 'draft')->orderByDesc('start_date')->first();
            $stats['payroll_net'] = $latestPeriod
                ? (float) $latestPeriod->runs()->where('status', '!=', 'dibatalkan')->latest('version')->value('total_net')
                : 0;
        }

        $stats['pending_approvals'] = app(ApprovalService::class)->pendingForUser($user)->count();

        $upcomingHolidays = Holiday::query()
            ->where('date', '>=', Carbon::now('Asia/Jakarta')->toDateString())
            ->orderBy('date')
            ->limit(5)
            ->get();

        $recentEmployees = null;
        if ($user->hasPermission('employee.view.all') || $user->hasPermission('employee.view.unit')) {
            $recentEmployees = Employee::query()
                ->visibleTo($user)
                ->with(['orgUnit', 'position'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        $attendanceChart = null;
        $categoryChart = null;
        if ($user->hasAnyPermission(['attendance.view.all', 'attendance.view.unit', 'attendance.report'])) {
            $days = collect(range(6, 0))->map(fn ($i) => Carbon::now('Asia/Jakarta')->subDays($i)->toDateString());
            $attendanceQuery = \App\Models\Attendance::query()
                ->whereIn('work_date', $days)
                ->whereIn('employee_id', Employee::query()->visibleTo($user)->select('id'));
            $rows = $attendanceQuery->selectRaw('work_date, status, count(*) as total')->groupBy('work_date', 'status')->get();
            $attendanceChart = [
                'labels' => $days->map(fn ($d) => Carbon::parse($d)->translatedFormat('d M'))->values(),
                'hadir' => $days->map(fn ($d) => (int) $rows->where('work_date', $d)->where('status', 'hadir')->sum('total'))->values(),
                'terlambat' => $days->map(fn ($d) => (int) $rows->where('work_date', $d)->where('status', 'terlambat')->sum('total'))->values(),
                'izin' => $days->map(fn ($d) => (int) $rows->where('work_date', $d)->whereIn('status', ['cuti', 'izin', 'sakit', 'dispensasi'])->sum('total'))->values(),
            ];
        }
        if ($user->hasAnyPermission(['employee.view.all', 'employee.view.unit'])) {
            $catRows = Employee::query()->visibleTo($user)->where('is_active', true)
                ->selectRaw('employee_category, count(*) as total')->groupBy('employee_category')->pluck('total', 'employee_category');
            $categoryChart = [
                'labels' => $catRows->keys()->map(fn ($k) => ucfirst(str_replace('_', ' ', $k)))->values(),
                'data' => $catRows->values(),
            ];
        }

        return view('dashboard', compact('employee', 'stats', 'upcomingHolidays', 'recentEmployees', 'attendanceChart', 'categoryChart'));
    }
}
