<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeTemplateExport;
use App\Exports\EmployeesExport;
use App\Imports\EmployeesImport;
use App\Models\Education;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamily;
use App\Models\EmployeePayrollProfile;
use App\Models\EmployeeStatusHistory;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\AuditLogger;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
        private readonly AuditLogger $audit
    ) {
    }

    public function index(Request $request): View
    {
        $this->requireAnyPermission($request->user(), [
            'employee.view.all', 'employee.view.unit', 'employee.view.self',
        ]);

        $query = Employee::query()
            ->visibleTo($request->user())
            ->with(['orgUnit:id,name', 'position:id,name']);

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('employee_category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('employment_status', $status);
        }

        if ($request->filled('org_unit_id')) {
            $query->whereIn('org_unit_id', OrgUnit::descendantIds([(int) $request->input('org_unit_id')]));
        }

        return view('employees.index', [
            'employees' => $query->orderBy('full_name')->paginate(15)->withQueryString(),
            'orgUnits' => OrgUnit::query()->orderBy('level')->orderBy('name')->pluck('name', 'id'),
            'canCreate' => $request->user()->hasPermission('employee.create'),
            'canExport' => $request->user()->hasPermission('employee.export'),
            'canImport' => $request->user()->hasPermission('employee.import'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->requirePermission($request->user(), 'employee.create');

        return view('employees.form', [
            'employee' => new Employee([
                'employee_category' => 'tetap',
                'employment_status' => 'aktif',
                'gender' => 'laki-laki',
                'is_active' => true,
                'hire_date' => now('Asia/Jakarta')->toDateString(),
            ]),
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission($request->user(), 'employee.create');

        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('employees/photo', 'local');
        }
        unset($data['photo'], $data['education_id'], $data['dependents_count']);

        $employee = $this->employees->create($data, $request->user());

        $this->audit->recordRaw('view_sensitive', $employee->getMorphClass(), $employee->id, null, [
            'action' => 'create_employee',
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Pegawai berhasil ditambahkan dengan NIP '.$employee->nip.'.');
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->authorizeView($request->user(), $employee);

        $employee->load([
            'orgUnit', 'position', 'workLocation', 'supervisor',
            'families', 'educations.education', 'bankAccounts', 'documents',
            'statusHistories', 'assignments.orgUnit', 'assignments.position',
            'payrollProfile.education',
        ]);

        $user = $request->user();
        $canSensitive = $user->hasPermission('employee.sensitive.view');
        $canPayroll = $user->hasPermission('employee.payroll_profile.manage')
            || $user->hasPermission('payroll.component.view');

        if (($canSensitive || $canPayroll) && ! $user->hasPermission('employee.view.self')) {
            // akses sensitif tetap dicatat
        }

        if ($canSensitive || $canPayroll) {
            $this->audit->recordRaw('view_sensitive', $employee->getMorphClass(), $employee->id);
        }

        return view('employees.show', [
            'employee' => $employee,
            'canSensitive' => $canSensitive,
            'canPayroll' => $canPayroll,
            'canUpdate' => $user->hasPermission('employee.update'),
            'canManageDocuments' => $user->hasPermission('employee.document.manage'),
            'canViewDocuments' => $user->hasPermission('employee.document.view'),
            'options' => $this->formOptions(),
        ]);
    }

    public function edit(Request $request, Employee $employee): View
    {
        $this->requirePermission($request->user(), 'employee.update');
        $this->authorizeView($request->user(), $employee);

        return view('employees.form', [
            'employee' => $employee,
            'options' => $this->formOptions(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->requirePermission($request->user(), 'employee.update');

        $data = $this->validated($request, $employee);

        if ($request->hasFile('photo')) {
            if ($employee->photo_path) {
                Storage::disk('local')->delete($employee->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('employees/photo', 'local');
        }
        unset($data['photo'], $data['education_id'], $data['dependents_count']);

        $this->employees->update($employee, $data, $request->user());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $this->requirePermission($request->user(), 'employee.delete');

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Pegawai berhasil dihapus (soft delete).');
    }

    public function photo(Request $request, Employee $employee): StreamedResponse
    {
        $this->authorizeView($request->user(), $employee);

        abort_unless($employee->photo_path && Storage::disk('local')->exists($employee->photo_path), 404);

        return Storage::disk('local')->response($employee->photo_path);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->requirePermission($request->user(), 'employee.export');

        $query = Employee::query()
            ->visibleTo($request->user())
            ->with(['orgUnit', 'position', 'workLocation', 'educations.education']);

        $this->audit->recordRaw('export', Employee::class, null, null, [
            'filters' => $request->query(),
            'format' => 'xlsx',
        ]);

        return Excel::download(
            new EmployeesExport($query),
            'data-pegawai-'.now('Asia/Jakarta')->format('Ymd-His').'.xlsx'
        );
    }

    public function importForm(Request $request): View
    {
        $this->requirePermission($request->user(), 'employee.import');

        return view('employees.import', [
            'batches' => \App\Models\ImportBatch::query()
                ->where('module', 'employees')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->requirePermission($request->user(), 'employee.import');

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.mimes' => 'Berkas harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $batch = \App\Models\ImportBatch::create([
            'module' => 'employees',
            'file_name' => $request->file('file')->getClientOriginalName(),
            'status' => 'processing',
            'created_by' => $request->user()->id,
        ]);

        $import = new EmployeesImport($request->user(), $batch);
        Excel::import($import, $request->file('file'));

        $batch->refresh();

        $message = "Impor selesai: {$batch->success_rows} berhasil, {$batch->failed_rows} gagal dari {$batch->total_rows} baris.";

        return redirect()
            ->route('employees.import.form')
            ->with($batch->failed_rows > 0 ? 'warning' : 'success', $message);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $this->requirePermission($request->user(), 'employee.import');

        return Excel::download(new EmployeeTemplateExport, 'template-impor-pegawai.xlsx');
    }

    protected function authorizeView(User $user, Employee $employee): void
    {
        if ($user->hasPermission('employee.view.all')) {
            return;
        }

        if ($user->employee_id && $user->employee_id === $employee->id) {
            return;
        }

        if ($user->hasPermission('employee.view.unit') && $employee->canBeViewedBy($user)) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke data pegawai ini.');
    }

    protected function requirePermission(User $user, string $permission): void
    {
        abort_unless($user->hasPermission($permission), 403, 'Anda tidak memiliki hak akses untuk tindakan ini.');
    }

    protected function requireAnyPermission(User $user, array $permissions): void
    {
        abort_unless($user->hasAnyPermission($permissions), 403, 'Anda tidak memiliki hak akses untuk halaman ini.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'orgUnits' => OrgUnit::query()->where('is_active', true)->orderBy('level')->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'workLocations' => WorkLocation::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'educations' => Education::query()->orderBy('level_order')->pluck('name', 'id'),
            'supervisors' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Employee $employee = null): array
    {
        $nikRule = ['nullable', 'digits:16'];
        if ($request->filled('nik')) {
            $nikRule[] = function (string $attribute, mixed $value, \Closure $fail) use ($employee) {
                $hash = hash('sha256', (string) $value);
                $exists = Employee::query()
                    ->where('nik_hash', $hash)
                    ->when($employee, fn ($q) => $q->whereKeyNot($employee->id))
                    ->exists();
                if ($exists) {
                    $fail('NIK sudah terdaftar pada pegawai lain.');
                }
            };
        }

        $data = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'nip')->ignore($employee?->id)],
            'nik' => $nikRule,
            'full_name' => ['required', 'string', 'max:150'],
            'nick_name' => ['nullable', 'string', 'max:60'],
            'gender' => ['required', Rule::in(['laki-laki', 'perempuan'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'religion' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['required', Rule::in(['lajang', 'menikah', 'cerai', 'cerai_mati'])],
            'blood_type' => ['nullable', 'string', 'max:3'],
            'address' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'employee_category' => ['required', Rule::in(['tetap', 'harian', 'khidmah'])],
            'khidmah_type' => ['nullable', 'string', 'max:20'],
            'employment_status' => ['required', Rule::in(['aktif', 'probation', 'cuti_panjang', 'nonaktif', 'berhenti'])],
            'org_unit_id' => ['nullable', 'exists:org_units,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'direct_supervisor_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee?->id])],
            'work_location_id' => ['nullable', 'exists:work_locations,id'],
            'hire_date' => ['required', 'date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'termination_reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'education_id' => ['nullable', 'exists:educations,id'],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:3'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['nip'] ?? null) && $employee) {
            unset($data['nip']);
        }

        return $data;
    }
}
