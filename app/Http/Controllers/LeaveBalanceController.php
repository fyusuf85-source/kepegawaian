<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('leave.balance.manage'), 403);

        $year = (int) $request->input('year', now('Asia/Jakarta')->year);

        $query = LeaveBalance::query()
            ->with(['employee:id,nip,full_name', 'leaveType:id,code,name'])
            ->where('year', $year);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', (int) $request->input('leave_type_id'));
        }

        return view('leave.balances', [
            'balances' => $query->orderBy('employee_id')->paginate(20)->withQueryString(),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'),
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('leave.balance.manage'), 403);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'entitlement_days' => ['required', 'numeric', 'min:0', 'max:366'],
            'adjustment_days' => ['nullable', 'numeric', 'between:-366,366'],
        ]);

        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'year' => $data['year'],
            ],
            [
                'entitlement_days' => $data['entitlement_days'],
                'adjustment_days' => $data['adjustment_days'] ?? 0,
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'Saldo izin/cuti disimpan.');
    }
}
