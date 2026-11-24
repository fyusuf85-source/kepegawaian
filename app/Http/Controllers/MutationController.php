<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\ApprovalInstance;
use App\Models\Decree;
use App\Models\Employee;
use App\Models\Mutation;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Services\ApprovalService;
use App\Services\MutationService;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MutationController extends Controller
{
    use GuardsHrAccess;

    public function __construct(
        private readonly MutationService $mutations,
        private readonly ApprovalService $approvals,
        private readonly NumberSequenceService $sequences
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'mutation.view');

        $query = Mutation::query()->with([
            'employee:id,nip,full_name,org_unit_id',
            'fromOrgUnit:id,name', 'toOrgUnit:id,name',
            'fromPosition:id,name', 'toPosition:id,name',
        ]);
        $this->scopeEmployeeRecords($query, $request->user());

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('q'))) {
            $query->whereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%"));
        }

        return view('hr.mutations.index', [
            'mutations' => $query->orderByDesc('effective_date')->paginate(15)->withQueryString(),
            'canManage' => $request->user()->hasPermission('mutation.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'mutation.manage');

        return view('hr.mutations.form', [
            'mutation' => new Mutation([
                'mutation_type' => 'mutasi',
                'effective_date' => $this->mutations->defaultEffectiveDate(),
                'employee_id' => $request->integer('employee_id') ?: null,
            ]),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'mutation.manage');

        $mutation = $this->mutations->create($this->validated($request), $request->user());

        return redirect()->route('mutations.show', $mutation)->with('success', 'Usulan mutasi dibuat.');
    }

    public function show(Request $request, Mutation $mutation): View
    {
        $this->ensurePermission($request, 'mutation.view');
        $this->ensureRecordVisible($request, $mutation->employee_id);

        $mutation->load([
            'employee.orgUnit:id,name', 'employee.position:id,name',
            'fromOrgUnit:id,name', 'toOrgUnit:id,name',
            'fromPosition:id,name', 'toPosition:id,name',
            'fromSupervisor:id,full_name', 'toSupervisor:id,full_name',
            'decree', 'requester:id,name', 'approver:id,name',
        ]);

        $approval = ApprovalInstance::query()
            ->where('subject_type', $mutation->getMorphClass())
            ->where('subject_id', $mutation->id)
            ->with(['flow.steps.role', 'actions.actor', 'requester'])
            ->latest()
            ->first();

        return view('hr.mutations.show', [
            'mutation' => $mutation,
            'approval' => $approval,
            'canManage' => $request->user()->hasPermission('mutation.manage'),
            'canManageDecree' => $request->user()->hasPermission('decree.manage'),
        ]);
    }

    public function submit(Request $request, Mutation $mutation): RedirectResponse
    {
        $this->ensurePermission($request, 'mutation.manage');
        $this->ensureRecordVisible($request, $mutation->employee_id);

        abort_unless($mutation->status === 'draf', 403, 'Hanya usulan draf yang dapat diajukan.');

        $this->approvals->submit($mutation, $request->user());

        return back()->with('success', 'Usulan mutasi diajukan untuk persetujuan.');
    }

    public function apply(Request $request, Mutation $mutation): RedirectResponse
    {
        $this->ensurePermission($request, 'mutation.manage');
        $this->ensureRecordVisible($request, $mutation->employee_id);

        $this->mutations->apply($mutation, $request->user());

        return back()->with('success', 'Mutasi diterapkan dan riwayat penempatan diperbarui.');
    }

    public function createDecree(Request $request, Mutation $mutation): RedirectResponse
    {
        $this->ensurePermission($request, 'decree.manage');
        $this->ensureRecordVisible($request, $mutation->employee_id);

        if ($mutation->decree_id) {
            return back()->withErrors(['decree' => 'Mutasi ini sudah memiliki SK terkait.']);
        }

        $data = $request->validate([
            'decree_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $decree = Decree::create([
            'employee_id' => $mutation->employee_id,
            'decree_type' => 'mutasi',
            'number' => $this->sequences->next('decree'),
            'decree_date' => $data['decree_date'],
            'effective_date' => $mutation->effective_date,
            'title' => $data['title'],
            'description' => $data['description'] ?? $mutation->reason,
            'status' => 'draf',
            'created_by' => $request->user()->id,
        ]);

        $mutation->update(['decree_id' => $decree->id]);

        return redirect()->route('decrees.show', $decree)->with('success', 'Draf SK mutasi dibuat.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'mutation_type' => ['required', Rule::in(['mutasi', 'promosi', 'demosi', 'perubahan_jabatan', 'penempatan_ulang'])],
            'to_org_unit_id' => ['nullable', 'exists:org_units,id'],
            'to_position_id' => ['nullable', 'exists:positions,id'],
            'to_supervisor_id' => ['nullable', 'exists:employees,id'],
            'effective_date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name', 'nip', 'org_unit_id', 'position_id']),
            'orgUnits' => OrgUnit::query()->where('is_active', true)->orderBy('level')->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
