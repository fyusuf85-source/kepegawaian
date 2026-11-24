<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\JobVacancy;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\WorkLocation;
use App\Services\ApplicantService;
use App\Services\NumberSequenceService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantController extends Controller
{
    use GuardsHrAccess;

    public function __construct(
        private readonly ApplicantService $applicants,
        private readonly NumberSequenceService $sequences
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'applicant.view');

        $query = Applicant::query()
            ->with(['vacancy:id,code,title,org_unit_id', 'vacancy.orgUnit:id,name', 'education:id,name'])
            ->withCount('interviews');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        if ($request->filled('vacancy_id')) {
            $query->where('vacancy_id', (int) $request->input('vacancy_id'));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('recruitment.applicants.index', [
            'applicants' => $query->orderByDesc('created_at')->paginate(15)->withQueryString(),
            'vacancies' => JobVacancy::query()->orderByDesc('open_date')->pluck('title', 'id'),
            'canManage' => $request->user()->hasPermission('applicant.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'applicant.manage');

        return view('recruitment.applicants.form', [
            'applicant' => new Applicant([
                'status' => 'baru',
                'vacancy_id' => $request->integer('vacancy_id') ?: null,
            ]),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.manage');

        $data = $this->validated($request);
        $vacancy = JobVacancy::findOrFail($data['vacancy_id']);

        if (! $vacancy->isOpen()) {
            return back()->withInput()->withErrors([
                'vacancy_id' => 'Lowongan tidak sedang dibuka untuk penerimaan pelamar.',
            ]);
        }

        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : $this->sequences->next('applicant');
        $data['status'] = 'baru';

        $applicant = Applicant::create($data);

        return redirect()->route('applicants.show', $applicant)
            ->with('success', 'Data pelamar berhasil disimpan.');
    }

    public function show(Request $request, Applicant $applicant): View
    {
        $this->ensurePermission($request, 'applicant.view');

        $applicant->load([
            'vacancy.orgUnit:id,name', 'vacancy.position:id,name', 'education:id,name',
            'documents', 'interviews.interviewer:id,full_name', 'scores.scorer:id,name',
            'convertedEmployee:id,nip,full_name',
        ]);

        return view('recruitment.applicants.show', [
            'applicant' => $applicant,
            'canManage' => $request->user()->hasPermission('applicant.manage'),
            'canAssess' => $request->user()->hasPermission('interview.assess'),
            'canDecide' => $request->user()->hasPermission('applicant.decide'),
            'canConvert' => $request->user()->hasPermission('applicant.convert'),
            'interviewers' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
            'convertOptions' => $this->convertOptions(),
        ]);
    }

    public function edit(Request $request, Applicant $applicant): View
    {
        $this->ensurePermission($request, 'applicant.manage');

        if ($applicant->converted_employee_id) {
            return redirect()->route('applicants.show', $applicant)
                ->withErrors(['applicant' => 'Data pelamar yang sudah dikonversi tidak dapat diubah.']);
        }

        return view('recruitment.applicants.form', [
            'applicant' => $applicant,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.manage');

        if ($applicant->converted_employee_id) {
            return back()->withErrors(['applicant' => 'Data pelamar yang sudah dikonversi tidak dapat diubah.']);
        }

        $data = $this->validated($request, $applicant);
        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : $applicant->code;
        $applicant->update($data);

        return redirect()->route('applicants.show', $applicant)->with('success', 'Data pelamar berhasil diperbarui.');
    }

    public function destroy(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.manage');

        if ($applicant->converted_employee_id) {
            return back()->withErrors(['applicant' => 'Pelamar sudah dikonversi menjadi pegawai.']);
        }

        foreach ($applicant->documents as $document) {
            Storage::disk('local')->delete($document->file_path);
        }

        $applicant->delete();

        return redirect()->route('applicants.index')->with('success', 'Data pelamar berhasil dihapus.');
    }

    public function storeDocument(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.manage');

        $data = $request->validate([
            'doc_type' => ['required', Rule::in(['ktp', 'kk', 'ijazah', 'transkrip', 'cv', 'sertifikat', 'foto', 'lainnya'])],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'file.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran dokumen maksimal 10 MB.',
        ]);

        $applicant->documents()->create([
            'doc_type' => $data['doc_type'],
            'file_path' => $request->file('file')->store('applicants/documents/'.$applicant->id, 'local'),
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Dokumen pelamar berhasil diunggah.');
    }

    public function downloadDocument(Request $request, Applicant $applicant, ApplicantDocument $document): StreamedResponse
    {
        $this->ensurePermission($request, 'applicant.view');
        abort_unless($document->applicant_id === $applicant->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('local')->download(
            $document->file_path,
            Format::filename($document->doc_type.'-'.$applicant->code).'.'.pathinfo($document->file_path, PATHINFO_EXTENSION)
        );
    }

    public function destroyDocument(Request $request, Applicant $applicant, ApplicantDocument $document): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.manage');
        abort_unless($document->applicant_id === $applicant->id, 404);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Dokumen pelamar dihapus.');
    }

    public function transition(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(['seleksi_administrasi', 'wawancara', 'dipertimbangkan', 'ditolak'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->applicants->transition($applicant, $data['status'], $data['note'] ?? null, $request->user());

        return back()->with('success', 'Status pelamar diperbarui.');
    }

    public function storeInterview(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'interview.assess');

        $data = $request->validate([
            'stage' => ['required', Rule::in(['hrd', 'kepala_bagian', 'pimpinan'])],
            'scheduled_at' => ['required', 'date'],
            'interviewer_id' => ['nullable', 'exists:employees,id'],
            'mode' => ['required', Rule::in(['tatap_muka', 'online'])],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $applicant->interviews()->create($data + ['status' => 'dijadwalkan']);

        if ($applicant->status === 'seleksi_administrasi') {
            $applicant->update(['status' => 'wawancara']);
        }

        return back()->with('success', 'Jadwal wawancara disimpan.');
    }

    public function updateInterview(Request $request, Applicant $applicant, Interview $interview): RedirectResponse
    {
        $this->ensurePermission($request, 'interview.assess');
        abort_unless($interview->applicant_id === $applicant->id, 404);

        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['nullable', Rule::in(['lulus', 'tidak_lulus', 'ditunda'])],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['dijadwalkan', 'selesai', 'dibatalkan'])],
        ]);

        $interview->update($data);

        return back()->with('success', 'Hasil wawancara diperbarui.');
    }

    public function storeScores(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'interview.assess');

        $data = $request->validate([
            'scores' => ['nullable', 'array'],
            'scores.*.criterion' => ['nullable', 'string', 'max:100'],
            'scores.*.weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = collect($data['scores'] ?? [])
            ->filter(fn (array $row) => filled($row['criterion'] ?? null))
            ->map(fn (array $row) => [
                'criterion' => $row['criterion'],
                'weight' => $row['weight'] ?? 0,
                'score' => $row['score'] ?? 0,
                'notes' => $row['notes'] ?? null,
            ])
            ->values()
            ->all();

        $this->applicants->saveScores($applicant, $rows, $request->user());

        return back()->with('success', 'Penilaian berbobot disimpan.');
    }

    public function decide(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.decide');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['diterima', 'ditolak'])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->applicants->decide($applicant, $data['decision'], $data['reason'], $request->user());

        return back()->with('success', 'Keputusan penerimaan disimpan.');
    }

    public function convertForm(Request $request, Applicant $applicant): View|RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.convert');

        if (! $applicant->canBeConverted()) {
            return redirect()->route('applicants.show', $applicant)
                ->withErrors(['applicant' => 'Hanya pelamar berstatus diterima dan belum dikonversi yang dapat diproses.']);
        }

        return view('recruitment.applicants.convert', [
            'applicant' => $applicant->load('vacancy', 'education'),
            'options' => $this->convertOptions(),
        ]);
    }

    public function convert(Request $request, Applicant $applicant): RedirectResponse
    {
        $this->ensurePermission($request, 'applicant.convert');

        $data = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', 'unique:employees,nip'],
            'nick_name' => ['nullable', 'string', 'max:60'],
            'religion' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['required', Rule::in(['lajang', 'menikah', 'cerai', 'cerai_mati'])],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'org_unit_id' => ['required', 'exists:org_units,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'work_location_id' => ['nullable', 'exists:work_locations,id'],
            'direct_supervisor_id' => ['nullable', 'exists:employees,id'],
            'employee_category' => ['required', Rule::in(['tetap', 'harian', 'khidmah'])],
            'employment_status' => ['required', Rule::in(['aktif', 'probation', 'cuti_panjang', 'nonaktif', 'berhenti'])],
            'hire_date' => ['required', 'date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
        ]);

        $employee = $this->applicants->convert($applicant, $data, $request->user());

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Pelamar berhasil dikonversi menjadi pegawai.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Applicant $applicant = null): array
    {
        return $request->validate([
            'vacancy_id' => ['required', 'exists:job_vacancies,id'],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('applicants', 'code')->ignore($applicant?->id)],
            'full_name' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'digits:16'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['laki-laki', 'perempuan'])],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'education_id' => ['nullable', 'exists:educations,id'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [
            'vacancies' => JobVacancy::query()->orderByDesc('open_date')->get(['id', 'code', 'title', 'status', 'open_date', 'close_date']),
            'educations' => Education::query()->where('is_active', true)->orderBy('level_order')->pluck('name', 'id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function convertOptions(): array
    {
        return [
            'orgUnits' => OrgUnit::query()->where('is_active', true)->orderBy('level')->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'workLocations' => WorkLocation::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'supervisors' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ];
    }
}
