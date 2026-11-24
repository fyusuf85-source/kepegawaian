<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollAdjustmentController extends Controller
{
    use GuardsHrAccess;

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'payroll.adjustment.manage');
        
        $query = PayrollAdjustment::query()
            ->with(['employee:id,nip,full_name', 'period', 'component'])
            ->latest();

        if ($periodId = $request->input('payroll_period_id')) {
            $query->where('payroll_period_id', $periodId);
        }

        return view('payroll.adjustments.index', [
            'adjustments' => $query->paginate(15),
            'periods' => PayrollPeriod::latest('start_date')->get(['id', 'code']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'payroll.adjustment.manage');
        
        return view('payroll.adjustments.form', [
            'adjustment' => new PayrollAdjustment(),
            'periods' => PayrollPeriod::where('status', '!=', 'dikunci')->latest('start_date')->get(),
            'employees' => Employee::active()->get(['id', 'nip', 'full_name']),
            'components' => PayrollComponent::where('is_active', true)->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'payroll.adjustment.manage');
        
        $data = $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'employee_id' => 'required|exists:employees,id',
            'component_id' => 'required|exists:payroll_components,id',
            'type' => 'required|in:penghasilan,potongan',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        PayrollAdjustment::create($data + [
            'status' => 'menunggu',
            'requested_by' => $request->user()->id,
        ]);

        return redirect()->route('payroll.adjustments.index')->with('success', 'Penyesuaian payroll berhasil diajukan.');
    }

    public function approve(Request $request, PayrollAdjustment $adjustment): RedirectResponse
    {
        $this->ensurePermission($request, 'payroll.lock'); // Only high level can approve adjustment
        
        $adjustment->update([
            'status' => 'disetujui',
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Penyesuaian disetujui.');
    }

    public function destroy(Request $request, PayrollAdjustment $adjustment): RedirectResponse
    {
        $this->ensurePermission($request, 'payroll.adjustment.manage');
        abort_unless($adjustment->status === 'menunggu', 403, 'Hanya penyesuaian yang menunggu yang dapat dihapus.');
        
        $adjustment->delete();
        return back()->with('success', 'Penyesuaian dihapus.');
    }
}
