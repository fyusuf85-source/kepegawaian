<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\ApprovalInstance;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Services\ApprovalService;
use App\Services\ContractService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    use GuardsHrAccess;

    public function __construct(
        private readonly ContractService $contracts,
        private readonly ApprovalService $approvals
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'contract.view');

        $query = EmploymentContract::query()->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name']);
        $this->scopeEmployeeRecords($query, $request->user());

        if ($search = trim((string) $request->input('q'))) {
            $query->where(fn ($q) => $q->where('contract_number', 'like', "%{$search}%")
                ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        return view('hr.contracts.index', [
            'contracts' => $query->orderByDesc('start_date')->paginate(15)->withQueryString(),
            'employees' => Employee::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'canManage' => $request->user()->hasPermission('contract.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'contract.manage');

        return view('hr.contracts.form', [
            'contract' => new EmploymentContract([
                'contract_type' => 'pkwt',
                'start_date' => now('Asia/Jakarta')->toDateString(),
                'status' => 'draf',
                'employee_id' => $request->integer('employee_id') ?: null,
            ]),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'contract.manage');

        $contract = $this->contracts->create($this->validated($request), $request->user());

        return redirect()->route('contracts.show', $contract)->with('success', 'Kontrak kerja berhasil dibuat.');
    }

    public function show(Request $request, EmploymentContract $contract): View
    {
        $this->ensurePermission($request, 'contract.view');
        $this->ensureRecordVisible($request, $contract->employee_id);

        $contract->load(['employee.orgUnit:id,name', 'employee.position:id,name', 'previousContract', 'renewals']);

        $approval = ApprovalInstance::query()
            ->where('subject_type', $contract->getMorphClass())
            ->where('subject_id', $contract->id)
            ->with(['flow.steps.role', 'actions.actor', 'requester'])
            ->latest()
            ->first();

        return view('hr.contracts.show', [
            'contract' => $contract,
            'approval' => $approval,
            'canManage' => $request->user()->hasPermission('contract.manage'),
        ]);
    }

    public function edit(Request $request, EmploymentContract $contract): View
    {
        $this->ensurePermission($request, 'contract.manage');
        $this->ensureRecordVisible($request, $contract->employee_id);

        abort_unless(in_array($contract->status, ['draf', 'dibatalkan'], true), 403, 'Kontrak yang sudah aktif tidak dapat diubah.');

        return view('hr.contracts.form', [
            'contract' => $contract,
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function update(Request $request, EmploymentContract $contract): RedirectResponse
    {
        $this->ensurePermission($request, 'contract.manage');
        $this->ensureRecordVisible($request, $contract->employee_id);

        abort_unless(in_array($contract->status, ['draf', 'dibatalkan'], true), 403, 'Kontrak yang sudah aktif tidak dapat diubah.');

        $contract->update($this->validated($request, $contract));

        return redirect()->route('contracts.show', $contract)->with('success', 'Kontrak kerja diperbarui.');
    }

    public function destroy(Request $request, EmploymentContract $contract): RedirectResponse
    {
        $this->ensurePermission($request, 'contract.manage');
        $this->ensureRecordVisible($request, $contract->employee_id);

        if ($contract->status !== 'draf' || $contract->renewals()->exists()) {
            return back()->withErrors(['delete' => 'Hanya kontrak draf tanpa perpanjangan yang dapat dihapus.']);
        }

        if ($contract->document_path) {
            Storage::disk('local')->delete($contract->document_path);
        }

        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Kontrak kerja dihapus.');
    }

    public function submit(Request $request, EmploymentContract $contract): RedirectResponse
    {
        $this->ensurePermission($request, 'contract.manage');
        $this->ensureRecordVisible($request, $contract->employee_id);

        abort_unless($contract->status === 'draf', 403, 'Hanya kontrak draf yang dapat diajukan.');

        $this->approvals->submit($contract, $request->user());

        return back()->with('success', 'Kontrak diajukan untuk persetujuan.');
    }

    public function renewForm(Request $request, EmploymentContract $contract): View
    {
        $this->ensurePermission($request, 'contract.manage');
        $this->ensureRecordVisible($request, $contract->employee_id);

        return view('hr.contracts.form', [
            'contract' => new EmploymentContract([
                'employee_id' => $contract->employee_id,
                'contract_type' => $contract->contract_type,
                'start_date' => $contract->end_date?->copy()->addDay()->toDateString(),
                'previous_contract_id' => $contract->id,
                'status' => 'draf',
            ]),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
            'previous' => $contract,
        ]);
    }

    public function renew(Request $request, EmploymentContract $contract): RedirectResponse
    {
        $this->ensurePermission($request, 'contract.manage');
        $this->ensureRecordVisible($request, $contract->employee_id);

        $new = $this->contracts->renew($contract, $this->validated($request), $request->user());

        return redirect()->route('contracts.show', $new)->with('success', 'Draf perpanjangan kontrak dibuat.');
    }

    public function pdf(Request $request, EmploymentContract $contract): StreamedResponse
    {
        $this->ensurePermission($request, 'contract.view');
        $this->ensureRecordVisible($request, $contract->employee_id);

        $path = $this->contracts->generateDocument($contract, $request->user());

        return Storage::disk('local')->download($path, 'kontrak-'.Format::filename($contract->contract_number).'.pdf');
    }

    public function download(Request $request, EmploymentContract $contract): StreamedResponse
    {
        $this->ensurePermission($request, 'contract.view');
        $this->ensureRecordVisible($request, $contract->employee_id);
        abort_unless($contract->document_path && Storage::disk('local')->exists($contract->document_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('local')->download($contract->document_path, 'kontrak-'.Format::filename($contract->contract_number).'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?EmploymentContract $contract = null): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'contract_number' => ['nullable', 'string', 'max:100', Rule::unique('employment_contracts', 'contract_number')->ignore($contract?->id)],
            'contract_type' => ['required', Rule::in(['pkwtt', 'pkwt', 'percobaan', 'khidmah'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'signed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($contract?->exists) {
            unset($data['employee_id']);
        }

        return $data;
    }
}
