<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use App\Models\EmployeeFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeFamilyController extends Controller
{
    use EnsuresEmployeeAccess;

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);

        $employee->families()->create($this->validated($request));

        return back()->with('success', 'Data keluarga berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee, EmployeeFamily $family): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);
        abort_unless($family->employee_id === $employee->id, 404);

        $family->update($this->validated($request));

        return back()->with('success', 'Data keluarga berhasil diperbarui.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeFamily $family): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);
        abort_unless($family->employee_id === $employee->id, 404);

        $family->delete();

        return back()->with('success', 'Data keluarga berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'relation' => ['required', Rule::in(['pasangan', 'anak', 'ayah', 'ibu', 'saudara', 'lainnya'])],
            'name' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'digits:16'],
            'gender' => ['nullable', Rule::in(['laki-laki', 'perempuan'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'education' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'is_dependent' => ['nullable', 'boolean'],
            'is_working_at_pis' => ['nullable', 'boolean'],
            'marriage_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]) + [
            'is_dependent' => $request->boolean('is_dependent'),
            'is_working_at_pis' => $request->boolean('is_working_at_pis'),
        ];
    }
}
