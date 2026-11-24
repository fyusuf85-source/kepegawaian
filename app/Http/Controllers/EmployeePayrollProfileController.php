<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeePayrollProfileController extends Controller
{
    use EnsuresEmployeeAccess;

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.payroll_profile.manage');
        $this->ensureVisible($request->user(), $employee);

        $data = $request->validate([
            'education_id' => ['nullable', 'exists:educations,id'],
            'transport_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'position_allowance_class' => ['nullable', 'string', 'max:20'],
            'bpjs_kesehatan_no' => ['nullable', 'string', 'max:50'],
            'bpjs_tk_no' => ['nullable', 'string', 'max:50'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'tax_status' => ['nullable', Rule::in(['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'])],
            'dependents_count' => ['required', 'integer', 'min:0', 'max:3'],
            'salary_basis_override' => ['nullable', 'numeric', 'min:0'],
            'override_reason' => ['required_with:salary_basis_override', 'nullable', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
        ]);

        if (filled($data['salary_basis_override'] ?? null)
            && ! $request->user()->hasPermission('payroll.component.manage')) {
            abort(403, 'Hanya Keuangan yang dapat menetapkan nilai dasar gaji khusus.');
        }

        $data['updated_by'] = $request->user()->id;

        $employee->payrollProfile()->updateOrCreate(
            ['employee_id' => $employee->id],
            $data
        );

        return back()->with('success', 'Profil penggajian berhasil diperbarui.');
    }
}
