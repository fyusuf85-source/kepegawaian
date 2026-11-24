<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeeAssignmentController extends Controller
{
    use EnsuresEmployeeAccess;

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);

        $data = $request->validate([
            'org_unit_id' => ['required', 'exists:org_units,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'direct_supervisor_id' => ['nullable', 'exists:employees,id'],
            'work_location_id' => ['nullable', 'exists:work_locations,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'decision_number' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $data['created_by'] = $request->user()->id;

        $employee->assignments()
            ->whereNull('effective_to')
            ->where('effective_from', '<=', $data['effective_from'])
            ->update([
                'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
            ]);

        $employee->assignments()->create($data);

        $employee->update([
            'org_unit_id' => $data['org_unit_id'],
            'position_id' => $data['position_id'],
            'direct_supervisor_id' => $data['direct_supervisor_id'] ?? null,
            'work_location_id' => $data['work_location_id'] ?? null,
        ]);

        return back()->with('success', 'Riwayat penempatan berhasil ditambahkan.');
    }
}
