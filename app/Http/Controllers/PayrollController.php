<?php

namespace App\Http\Controllers;

use App\Exports\PayrollOperationalExport;
use App\Exports\PayrollReportExport;
use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\PayrollPeriod;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ApprovalService;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    use GuardsHrAccess;
    public function __construct(private readonly PayrollService $payroll, private readonly ApprovalService $approvals) {}
    protected function access(Request $r, string $permission): void { $this->ensurePermission($r, $permission); }
    public function index(Request $r): View { $this->access($r, 'payroll.view.all'); return view('payroll.index', ['periods' => PayrollPeriod::query()->latest('start_date')->paginate(15)]); }
    public function create(Request $r): View { $this->access($r, 'payroll.period.manage'); return view('payroll.form'); }
    public function store(Request $r): RedirectResponse { $this->access($r, 'payroll.period.manage'); $data = $r->validate(['code' => ['required','regex:/^\d{4}-(0[1-9]|1[0-2])$/','unique:payroll_periods,code'], 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'payment_date' => 'nullable|date', 'notes' => 'nullable|string']); $period = PayrollPeriod::create($data + ['status' => 'draft']); return redirect()->route('payroll.show', $period)->with('success', 'Periode payroll dibuat.'); }
    public function show(Request $r, PayrollPeriod $period): View
    {
        $this->access($r, 'payroll.view.all');
        $run = $period->runs()->where('status', '!=', 'dibatalkan')->latest('version')->first();
        return view('payroll.show', compact('period', 'run'));
    }
    public function generateSnapshot(Request $r, PayrollPeriod $period): RedirectResponse { $this->access($r, 'payroll.calculate'); $this->payroll->snapshot($period, $r->user()); return back()->with('success', 'Snapshot absensi dibuat.'); }
    public function calculate(Request $r, PayrollPeriod $period): RedirectResponse { $this->access($r, 'payroll.calculate'); $run = $this->payroll->calculateSkeleton($period, $r->user()); return back()->with('success', 'Payroll dihitung. Run versi '.$run->version.' dibuat.'); }
    public function submitApproval(Request $r, PayrollPeriod $period): RedirectResponse { $this->access($r, 'payroll.calculate'); $this->approvals->submit($period, $r->user()); return back()->with('success', 'Periode payroll dikirim ke alur persetujuan.'); }
    public function lock(Request $r, PayrollPeriod $period): RedirectResponse { $this->access($r, 'payroll.lock'); $this->payroll->lock($period, $r->user()); return back()->with('success', 'Periode payroll dikunci.'); }
    public function export(Request $r, PayrollPeriod $period)
    {
        $this->access($r, 'payroll.report');
        abort_unless($period->runs()->where('status', '!=', 'dibatalkan')->exists(), 404, 'Belum ada payroll run.');
        $report = $r->string('report')->toString();
        $allowed = ['payroll', 'transfer_gaji', 'transfer_bsi', 'transfer_snack', 'bpjs_tk', 'bpjs_kes', 'pajak'];
        abort_unless(in_array($report ?: 'payroll', $allowed, true), 422, 'Jenis laporan tidak dikenal.');
        if ($report === 'payroll' || $report === '') return Excel::download(new PayrollReportExport($period), 'payroll-'.$period->code.'.xlsx');
        return Excel::download(new PayrollOperationalExport($period, $report), $report.'-'.$period->code.'.xlsx');
    }
}
