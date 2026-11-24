<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\ApprovalInstance;
use App\Models\Employee;
use App\Models\EmployeeLetter;
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

class LetterController extends Controller
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
        $this->ensurePermission($request, 'letter.view');

        $query = EmployeeLetter::query()->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name']);
        $this->scopeEmployeeRecords($query, $request->user());

        if ($search = trim((string) $request->input('q'))) {
            $query->where(fn ($q) => $q->where('number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$search}%")));
        }

        if ($type = $request->input('letter_type')) {
            $query->where('letter_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('hr.letters.index', [
            'letters' => $query->orderByDesc('letter_date')->paginate(15)->withQueryString(),
            'canManage' => $request->user()->hasPermission('letter.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'letter.manage');

        return view('hr.letters.form', [
            'letter' => new EmployeeLetter([
                'letter_type' => 'keterangan',
                'letter_date' => now('Asia/Jakarta')->toDateString(),
                'status' => 'draf',
                'employee_id' => $request->integer('employee_id') ?: null,
            ]),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'letter.manage');

        $data = $this->validated($request);
        $data['number'] = filled($data['number'] ?? null) ? $data['number'] : $this->sequences->next('letter');
        $data['status'] = 'draf';
        $data['created_by'] = $request->user()->id;

        $letter = EmployeeLetter::create($data);

        return redirect()->route('letters.show', $letter)->with('success', 'Surat berhasil dibuat.');
    }

    public function show(Request $request, EmployeeLetter $letter): View
    {
        $this->ensurePermission($request, 'letter.view');
        $this->ensureRecordVisible($request, $letter->employee_id);

        $letter->load(['employee.orgUnit:id,name', 'employee.position:id,name', 'creator:id,name', 'approver:id,name']);

        $approval = ApprovalInstance::query()
            ->where('subject_type', $letter->getMorphClass())
            ->where('subject_id', $letter->id)
            ->with(['flow.steps.role', 'actions.actor', 'requester'])
            ->latest()
            ->first();

        return view('hr.letters.show', [
            'letter' => $letter,
            'approval' => $approval,
            'canManage' => $request->user()->hasPermission('letter.manage'),
        ]);
    }

    public function edit(Request $request, EmployeeLetter $letter): View
    {
        $this->ensurePermission($request, 'letter.manage');
        $this->ensureRecordVisible($request, $letter->employee_id);

        abort_unless(in_array($letter->status, ['draf', 'dibatalkan'], true), 403, 'Surat yang sudah diajukan tidak dapat diubah.');

        return view('hr.letters.form', [
            'letter' => $letter,
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function update(Request $request, EmployeeLetter $letter): RedirectResponse
    {
        $this->ensurePermission($request, 'letter.manage');
        $this->ensureRecordVisible($request, $letter->employee_id);

        abort_unless(in_array($letter->status, ['draf', 'dibatalkan'], true), 403, 'Surat yang sudah diajukan tidak dapat diubah.');

        $data = $this->validated($request, $letter);
        $data['number'] = filled($data['number'] ?? null) ? $data['number'] : $letter->number;
        $letter->update($data);

        return redirect()->route('letters.show', $letter)->with('success', 'Surat diperbarui.');
    }

    public function destroy(Request $request, EmployeeLetter $letter): RedirectResponse
    {
        $this->ensurePermission($request, 'letter.manage');
        $this->ensureRecordVisible($request, $letter->employee_id);

        if ($letter->status !== 'draf') {
            return back()->withErrors(['delete' => 'Hanya surat draf yang dapat dihapus.']);
        }

        if ($letter->document_path) {
            Storage::disk('local')->delete($letter->document_path);
        }

        $letter->delete();

        return redirect()->route('letters.index')->with('success', 'Surat dihapus.');
    }

    public function submit(Request $request, EmployeeLetter $letter): RedirectResponse
    {
        $this->ensurePermission($request, 'letter.manage');
        $this->ensureRecordVisible($request, $letter->employee_id);

        abort_unless($letter->status === 'draf', 403, 'Hanya surat draf yang dapat diajukan.');

        $this->approvals->submit($letter, $request->user());

        return back()->with('success', 'Surat diajukan untuk persetujuan.');
    }

    public function send(Request $request, EmployeeLetter $letter): RedirectResponse
    {
        $this->ensurePermission($request, 'letter.manage');
        $this->ensureRecordVisible($request, $letter->employee_id);

        abort_unless($letter->status === 'disetujui', 403, 'Surat harus disetujui sebelum ditandai terkirim.');

        $letter->update(['status' => 'terkirim']);

        return back()->with('success', 'Surat ditandai terkirim.');
    }

    public function pdf(Request $request, EmployeeLetter $letter): StreamedResponse
    {
        $this->ensurePermission($request, 'letter.view');
        $this->ensureRecordVisible($request, $letter->employee_id);

        $letter->loadMissing('employee.orgUnit', 'employee.position');

        $path = $this->documents->generate(
            'SURAT-UMUM',
            [
                'nomor' => $letter->number,
                'perihal' => $letter->subject,
                'nama_pegawai' => $letter->employee?->full_name,
                'nip' => $letter->employee?->nip,
                'jabatan' => $letter->employee?->position?->name,
                'unit' => $letter->employee?->orgUnit?->name,
                'tanggal_surat' => $letter->letter_date?->format('d-m-Y'),
                'isi' => $letter->body ?: $letter->subject,
                'kota' => 'Sidoarjo',
                'tanggal_cetak' => now('Asia/Jakarta')->format('d-m-Y'),
            ],
            'surat',
            $letter->number.'-'.$letter->employee?->nip
        );

        $letter->update(['document_path' => $path]);

        return Storage::disk('local')->download($path, 'surat-'.Format::filename($letter->number).'.pdf');
    }

    public function download(Request $request, EmployeeLetter $letter): StreamedResponse
    {
        $this->ensurePermission($request, 'letter.view');
        $this->ensureRecordVisible($request, $letter->employee_id);
        abort_unless($letter->document_path && Storage::disk('local')->exists($letter->document_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('local')->download($letter->document_path, 'surat-'.Format::filename($letter->number).'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?EmployeeLetter $letter = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'letter_type' => ['required', Rule::in(['teguran', 'pembinaan', 'pemberhentian', 'keterangan', 'lain'])],
            'number' => ['nullable', 'string', 'max:100', Rule::unique('employee_letters', 'number')->ignore($letter?->id)],
            'letter_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);
    }
}
