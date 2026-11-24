<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeStatusHistoryController extends Controller
{
    use EnsuresEmployeeAccess;

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);

        $data = $request->validate([
            'status' => ['required', Rule::in(['aktif', 'probation', 'cuti_panjang', 'nonaktif', 'berhenti'])],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $employee->statusHistories()->create($data + ['created_by' => $request->user()->id]);

        if ($data['status'] === 'berhenti') {
            $employee->update([
                'is_active' => false,
                'employment_status' => 'berhenti',
                'termination_date' => $data['effective_date'],
                'termination_reason' => $data['reason'],
            ]);

            $this->audit->recordRaw('update', $employee->getMorphClass(), $employee->id, null, [
                'employment_status' => 'berhenti',
                'reason' => $data['reason'],
            ]);
        }

        return back()->with('success', 'Riwayat status pegawai berhasil dicatat.');
    }
}
