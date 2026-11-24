<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\JobVacancy;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VacancyController extends Controller
{
    use GuardsHrAccess;

    public function __construct(private readonly NumberSequenceService $sequences)
    {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'vacancy.view');

        $query = JobVacancy::query()
            ->with(['orgUnit:id,name', 'position:id,name'])
            ->withCount('applicants');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('recruitment.vacancies.index', [
            'vacancies' => $query->orderByDesc('open_date')->paginate(15)->withQueryString(),
            'canManage' => $request->user()->hasPermission('vacancy.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'vacancy.manage');

        return view('recruitment.vacancies.form', [
            'vacancy' => new JobVacancy([
                'employee_category' => 'tetap',
                'quota' => 1,
                'open_date' => now('Asia/Jakarta')->toDateString(),
                'close_date' => now('Asia/Jakarta')->addMonth()->toDateString(),
                'status' => 'draft',
            ]),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'vacancy.manage');

        $data = $this->validated($request);
        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : $this->sequences->next('vacancy');
        $data['created_by'] = $request->user()->id;

        $vacancy = JobVacancy::create($data);

        return redirect()->route('vacancies.show', $vacancy)->with('success', 'Lowongan berhasil dibuat.');
    }

    public function show(Request $request, JobVacancy $vacancy): View
    {
        $this->ensurePermission($request, 'vacancy.view');

        $vacancy->load(['orgUnit:id,name', 'position:id,name', 'applicants' => fn ($q) => $q->orderBy('full_name')]);

        return view('recruitment.vacancies.show', [
            'vacancy' => $vacancy,
            'canManage' => $request->user()->hasPermission('vacancy.manage'),
            'canManageApplicants' => $request->user()->hasPermission('applicant.manage'),
        ]);
    }

    public function edit(Request $request, JobVacancy $vacancy): View
    {
        $this->ensurePermission($request, 'vacancy.manage');

        return view('recruitment.vacancies.form', [
            'vacancy' => $vacancy,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        $this->ensurePermission($request, 'vacancy.manage');

        $data = $this->validated($request, $vacancy);
        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : $vacancy->code;
        $vacancy->update($data);

        return redirect()->route('vacancies.show', $vacancy)->with('success', 'Lowongan berhasil diperbarui.');
    }

    public function destroy(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        $this->ensurePermission($request, 'vacancy.manage');

        if ($vacancy->applicants()->exists()) {
            return back()->withErrors(['delete' => 'Lowongan sudah memiliki pelamar dan tidak boleh dihapus.']);
        }

        $vacancy->delete();

        return redirect()->route('vacancies.index')->with('success', 'Lowongan berhasil dihapus.');
    }

    public function updateStatus(Request $request, JobVacancy $vacancy): RedirectResponse
    {
        $this->ensurePermission($request, 'vacancy.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'dibuka', 'ditutup', 'dibatalkan'])],
        ]);

        $vacancy->update($data);

        return back()->with('success', 'Status lowongan diperbarui menjadi '.$data['status'].'.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?JobVacancy $vacancy = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('job_vacancies', 'code')->ignore($vacancy?->id)],
            'title' => ['required', 'string', 'max:150'],
            'org_unit_id' => ['required', 'exists:org_units,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'employee_category' => ['required', Rule::in(['tetap', 'harian', 'khidmah'])],
            'quota' => ['required', 'integer', 'min:1', 'max:1000'],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'open_date' => ['required', 'date'],
            'close_date' => ['required', 'date', 'after_or_equal:open_date'],
            'status' => ['required', Rule::in(['draft', 'dibuka', 'ditutup', 'dibatalkan'])],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [
            'orgUnits' => OrgUnit::query()->where('is_active', true)->orderBy('level')->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
