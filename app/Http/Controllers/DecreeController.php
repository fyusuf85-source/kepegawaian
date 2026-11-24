<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\ApprovalInstance;
use App\Models\Decree;
use App\Models\Employee;
use App\Services\ApprovalService;
use App\Services\DocumentPdfService;
use App\Services\NumberSequenceService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DecreeController extends Controller
{
    use GuardsHrAccess;

    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly ApprovalService $approvals,
        private readonly DocumentPdfService $documents
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'decree.view');

        $query = Decree::query()->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name']);
        $this->scopeEmployeeRecords($query, $request->user());

        if ($search = trim((string) $request->input('q'))) {
            $query->where(fn ($q) => $q->where('number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$search}%")));
        }

        if ($type = $request->input('decree_type')) {
            $query->where('decree_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('hr.decrees.index', [
            'decrees' => $query->orderByDesc('decree_date')->paginate(15)->withQueryString(),
            'canManage' => $request->user()->hasPermission('decree.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'decree.manage');

        return view('hr.decrees.form', [
            'decree' => new Decree([
                'decree_type' => 'pengangkatan',
                'decree_date' => now('Asia/Jakarta')->toDateString(),
                'effective_date' => now('Asia/Jakarta')->toDateString(),
                'status' => 'draf',
                'employee_id' => $request->integer('employee_id') ?: null,
            ]),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'decree.manage');

        $data = $this->validated($request);
        $data['number'] = filled($data['number'] ?? null) ? $data['number'] : $this->sequences->next('decree');
        $data['status'] = 'draf';
        $data['created_by'] = $request->user()->id;

        $decree = Decree::create($data);

        return redirect()->route('decrees.show', $decree)->with('success', 'SK berhasil dibuat.');
    }

    public function show(Request $request, Decree $decree): View
    {
        $this->ensurePermission($request, 'decree.view');
        $this->ensureRecordVisible($request, $decree->employee_id);

        $decree->load(['employee.orgUnit:id,name', 'employee.position:id,name', 'approver:id,name', 'mutations']);

        $approval = ApprovalInstance::query()
            ->where('subject_type', $decree->getMorphClass())
            ->where('subject_id', $decree->id)
            ->with(['flow.steps.role', 'actions.actor', 'requester'])
            ->latest()
            ->first();

        return view('hr.decrees.show', [
            'decree' => $decree,
            'approval' => $approval,
            'canManage' => $request->user()->hasPermission('decree.manage'),
        ]);
    }

    public function edit(Request $request, Decree $decree): View
    {
        $this->ensurePermission($request, 'decree.manage');
        $this->ensureRecordVisible($request, $decree->employee_id);

        abort_unless(in_array($decree->status, ['draf', 'ditolak'], true), 403, 'SK yang sudah diajukan tidak dapat diubah.');

        return view('hr.decrees.form', [
            'decree' => $decree,
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function update(Request $request, Decree $decree): RedirectResponse
    {
        $this->ensurePermission($request, 'decree.manage');
        $this->ensureRecordVisible($request, $decree->employee_id);

        abort_unless(in_array($decree->status, ['draf', 'ditolak'], true), 403, 'SK yang sudah diajukan tidak dapat diubah.');

        $data = $this->validated($request, $decree);
        $data['number'] = filled($data['number'] ?? null) ? $data['number'] : $decree->number;
        $decree->update($data);

        return redirect()->route('decrees.show', $decree)->with('success', 'SK diperbarui.');
    }

    public function destroy(Request $request, Decree $decree): RedirectResponse
    {
        $this->ensurePermission($request, 'decree.manage');
        $this->ensureRecordVisible($request, $decree->employee_id);

        if ($decree->status !== 'draf' || $decree->mutations()->exists()) {
            return back()->withErrors(['delete' => 'Hanya SK draf tanpa mutasi terkait yang dapat dihapus.']);
        }

        if ($decree->document_path) {
            Storage::disk('local')->delete($decree->document_path);
        }

        $decree->delete();

        return redirect()->route('decrees.index')->with('success', 'SK dihapus.');
    }

    public function submit(Request $request, Decree $decree): RedirectResponse
    {
        $this->ensurePermission($request, 'decree.manage');
        $this->ensureRecordVisible($request, $decree->employee_id);

        abort_unless($decree->status === 'draf', 403, 'Hanya SK draf yang dapat diajukan.');

        $this->approvals->submit($decree, $request->user());

        return back()->with('success', 'SK diajukan untuk persetujuan.');
    }

    public function pdf(Request $request, Decree $decree): StreamedResponse
    {
        $this->ensurePermission($request, 'decree.view');
        $this->ensureRecordVisible($request, $decree->employee_id);

        $decree->loadMissing('employee.orgUnit', 'employee.position');

        $path = $this->documents->generate(
            'SK-UMUM',
            [
                'nomor' => $decree->number,
                'judul' => $decree->title,
                'nama_pegawai' => $decree->employee?->full_name,
                'nip' => $decree->employee?->nip,
                'jabatan' => $decree->employee?->position?->name,
                'unit' => $decree->employee?->orgUnit?->name,
                'tanggal_sk' => $decree->decree_date?->format('d-m-Y'),
                'tanggal_efektif' => $decree->effective_date?->format('d-m-Y'),
                'isi' => $decree->description ?: $decree->title,
                'kota' => 'Sidoarjo',
                'tanggal_cetak' => now('Asia/Jakarta')->format('d-m-Y'),
            ],
            'sk',
            $decree->number.'-'.$decree->employee?->nip
        );

        $decree->update(['document_path' => $path]);

        return Storage::disk('local')->download($path, 'sk-'.Format::filename($decree->number).'.pdf');
    }

    public function download(Request $request, Decree $decree): StreamedResponse
    {
        $this->ensurePermission($request, 'decree.view');
        $this->ensureRecordVisible($request, $decree->employee_id);
        abort_unless($decree->document_path && Storage::disk('local')->exists($decree->document_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('local')->download($decree->document_path, 'sk-'.Format::filename($decree->number).'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Decree $decree = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'decree_type' => ['required', Rule::in(['pengangkatan', 'penempatan', 'perubahan_jabatan', 'mutasi', 'pemberhentian', 'lain'])],
            'number' => ['nullable', 'string', 'max:100', Rule::unique('decrees', 'number')->ignore($decree?->id)],
            'decree_date' => ['required', 'date'],
            'effective_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
