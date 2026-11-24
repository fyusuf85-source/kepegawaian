<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeEducationController extends Controller
{
    use EnsuresEmployeeAccess;

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);

        $data = $this->validated($request);

        if ($request->hasFile('certificate')) {
            $data['certificate_path'] = $request->file('certificate')->store('employees/certificates', 'local');
        }
        unset($data['certificate']);

        $education = $employee->educations()->create($data);

        if ($education->is_highest) {
            $employee->educations()->whereKeyNot($education->id)->update(['is_highest' => false]);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.update');
        $this->ensureVisible($request->user(), $employee);
        abort_unless($education->employee_id === $employee->id, 404);

        if ($education->certificate_path) {
            Storage::disk('local')->delete($education->certificate_path);
        }

        $education->delete();

        return back()->with('success', 'Riwayat pendidikan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'education_id' => ['required', 'exists:educations,id'],
            'institution' => ['required', 'string', 'max:150'],
            'major' => ['nullable', 'string', 'max:100'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'is_highest' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $data['is_highest'] = $request->boolean('is_highest');

        return $data;
    }
}
