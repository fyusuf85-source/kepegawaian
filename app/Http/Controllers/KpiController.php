<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\CoachingRecord;
use App\Models\Employee;
use App\Models\KpiAssessment;
use App\Models\KpiIndicator;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KpiController extends Controller
{
    use GuardsHrAccess;

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'kpi.view.self');

        $periods = KpiPeriod::query()->orderByDesc('start_date')->get();
        $templates = KpiTemplate::query()->with('indicators')->orderByDesc('is_active')->get();
        $assessments = $this->visibleAssessments($request)->with([
            'employee:id,full_name,nip', 'period:id,code', 'template:id,name', 'assessor:id,full_name',
        ])->orderByDesc('id')->paginate(20);

        return view('kpi.index', compact('periods', 'templates', 'assessments'));
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.period.manage');

        $data = $request->validate([
            'code' => 'required|string|max:10|unique:kpi_periods,code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['status'] = 'open';
        KpiPeriod::query()->create($data);

        return back()->with('success', 'Periode KPI dibuat.');
    }

    public function updatePeriodStatus(Request $request, KpiPeriod $period): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.period.manage');
        $data = $request->validate(['status' => 'required|in:draft,open,closed']);
        $period->update($data);

        return back()->with('success', 'Status periode KPI diperbarui.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.template.manage');

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:kpi_templates,name',
            'description' => 'nullable|string|max:1000',
            'indicators' => 'required|array|min:1',
            'indicators.*.code' => 'required|string|max:20',
            'indicators.*.name' => 'required|string|max:150',
            'indicators.*.weight' => 'required|numeric|min:0.01|max:100',
            'indicators.*.target_type' => 'required|in:min,max',
            'indicators.*.unit' => 'nullable|string|max:50',
        ]);

        $total = collect($data['indicators'])->sum('weight');
        if (abs((float) $total - 100.0) > 0.001) {
            throw ValidationException::withMessages([
                'indicators' => 'Total bobot indikator harus tepat 100%. Saat ini: '.$total.'%.',
            ]);
        }

        DB::transaction(function () use ($data): void {
            $template = KpiTemplate::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);
            foreach ($data['indicators'] as $indicator) {
                $template->indicators()->create($indicator);
            }
        });

        return back()->with('success', 'Template KPI dibuat dengan bobot 100%.');
    }

    public function createAssessment(Request $request): View
    {
        $this->ensurePermission($request, 'kpi.assess');

        $employees = Employee::query()
            ->visibleTo($request->user())
            ->where('is_active', true)
            ->where('id', '!=', $request->user()->employee_id ?? 0)
            ->get(['id', 'nip', 'full_name']);

        return view('kpi.assessments.create', [
            'employees' => $employees,
            'periods' => KpiPeriod::query()->where('status', 'open')->orderByDesc('start_date')->get(),
            'templates' => KpiTemplate::query()->where('is_active', true)->get(),
        ]);
    }

    public function storeAssessment(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.assess');

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'kpi_period_id' => 'required|exists:kpi_periods,id',
            'kpi_template_id' => 'required|exists:kpi_templates,id',
        ]);

        $this->ensureRecordVisible($request, (int) $data['employee_id']);

        if ((int) $data['employee_id'] === (int) ($request->user()->employee_id ?? 0)) {
            throw ValidationException::withMessages(['employee_id' => 'Penilai tidak dapat menilai dirinya sendiri.']);
        }

        $exists = KpiAssessment::query()->where([
            'employee_id' => $data['employee_id'],
            'kpi_period_id' => $data['kpi_period_id'],
            'kpi_template_id' => $data['kpi_template_id'],
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages(['employee_id' => 'Penilaian untuk pegawai, periode, dan template ini sudah ada.']);
        }

        $assessment = DB::transaction(function () use ($data, $request): KpiAssessment {
            $assessment = KpiAssessment::query()->create($data + [
                'assessor_id' => $request->user()->employee_id,
                'status' => 'draft',
                'final_score' => 0,
            ]);

            foreach (KpiIndicator::query()->where('kpi_template_id', $data['kpi_template_id'])->orderBy('code')->get() as $indicator) {
                $assessment->items()->create([
                    'kpi_indicator_id' => $indicator->id,
                    'target' => null,
                    'actual' => null,
                    'score' => 0,
                    'weighted_score' => 0,
                ]);
            }

            return $assessment;
        });

        return redirect()->route('kpi.assessments.edit', $assessment)->with('success', 'Penilaian KPI dibuat. Isi realisasi dan skor berikutnya.');
    }

    public function editAssessment(Request $request, KpiAssessment $assessment): View
    {
        $this->ensureCanAssess($request, $assessment);
        abort_unless($assessment->status === 'draft', 403, 'Penilaian terkunci tidak dapat diubah.');
        $assessment->load(['employee:id,full_name,nip', 'period', 'template', 'items.indicator']);

        return view('kpi.assessments.edit', compact('assessment'));
    }

    public function updateAssessment(Request $request, KpiAssessment $assessment): RedirectResponse
    {
        $this->ensureCanAssess($request, $assessment);
        abort_unless($assessment->status === 'draft', 403, 'Penilaian terkunci tidak dapat diubah.');

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:kpi_assessment_items,id',
            'items.*.target' => 'nullable|numeric|min:0',
            'items.*.actual' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($data, $assessment): void {
            foreach ($data['items'] as $itemData) {
                $item = $assessment->items()->whereKey($itemData['id'])->firstOrFail();
                $target = isset($itemData['target']) ? (float) $itemData['target'] : 0.0;
                $actual = isset($itemData['actual']) ? (float) $itemData['actual'] : 0.0;
                $score = $target > 0 ? min(100.0, round($actual / $target * 100, 2)) : 0.0;
                $weight = (float) $item->indicator->weight;
                $item->update([
                    'target' => $target,
                    'actual' => $actual,
                    'score' => $score,
                    'weighted_score' => round($score * $weight / 100, 2),
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $assessment->update([
                'final_score' => round((float) $assessment->items()->sum('weighted_score'), 2),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Penilaian KPI disimpan. Nilai akhir: '.$assessment->fresh()->final_score);
    }

    public function submitAssessment(Request $request, KpiAssessment $assessment): RedirectResponse
    {
        $this->ensureCanAssess($request, $assessment);
        abort_unless($assessment->status === 'draft' && $assessment->final_score > 0, 422, 'Isi penilaian sebelum mengajukan.');
        $assessment->update(['status' => 'submitted']);

        $this->notifications->sendToPermission(
            'kpi.verify',
            'kpi',
            'Penilaian KPI perlu verifikasi',
            'Penilaian KPI '.$assessment->employee?->full_name.' menunggu verifikasi HRD.',
            ['kpi_assessment_id' => $assessment->id]
        );

        return back()->with('success', 'Penilaian KPI diajukan untuk verifikasi HRD.');
    }

    public function verifyAssessment(Request $request, KpiAssessment $assessment): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.verify');
        abort_unless($assessment->status === 'submitted', 422, 'Hanya penilaian yang diajukan yang dapat diverifikasi.');
        $assessment->update(['status' => 'verified']);

        $this->notifications->sendToPermission(
            'kpi.approve',
            'kpi',
            'Penilaian KPI menunggu persetujuan',
            'Penilaian KPI '.$assessment->employee?->full_name.' telah diverifikasi dan menunggu persetujuan Pimpinan.',
            ['kpi_assessment_id' => $assessment->id]
        );

        return back()->with('success', 'Penilaian KPI diverifikasi.');
    }

    public function approveAssessment(Request $request, KpiAssessment $assessment): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.approve');
        abort_unless($assessment->status === 'verified', 422, 'Penilaian harus diverifikasi HRD sebelum disetujui.');
        $assessment->update(['status' => 'approved']);

        if ($user = $assessment->employee?->user) {
            $this->notifications->send(
                $user,
                'kpi',
                'Penilaian KPI disetujui',
                'Penilaian KPI Anda untuk periode '.$assessment->period?->code.' telah disetujui dengan nilai '.$assessment->final_score.'.',
                ['kpi_assessment_id' => $assessment->id]
            );
        }

        return back()->with('success', 'Penilaian KPI disetujui dan dikunci.');
    }

    public function unlockAssessment(Request $request, KpiAssessment $assessment): RedirectResponse
    {
        $this->ensurePermission($request, 'kpi.unlock');
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $assessment->update([
            'status' => 'draft',
            'notes' => trim(($assessment->notes ? $assessment->notes."\n" : '').'Dibuka kembali: '.$data['reason']),
        ]);

        return back()->with('success', 'Penilaian KPI dibuka kembali.');
    }

    public function showAssessment(Request $request, KpiAssessment $assessment): View
    {
        abort_unless($this->visibleAssessments($request)->whereKey($assessment->id)->exists(), 403);
        $assessment->load(['employee:id,full_name,nip', 'period', 'template', 'assessor:id,full_name', 'items.indicator']);

        return view('kpi.assessments.show', compact('assessment'));
    }

    private function visibleAssessments(Request $request): Builder
    {
        $user = $request->user();

        if ($user->hasPermission('kpi.view.all')) {
            return KpiAssessment::query();
        }

        if ($user->hasPermission('kpi.view.unit')) {
            return KpiAssessment::query()->whereIn(
                'employee_id',
                Employee::query()->visibleTo($user)->select('id')
            );
        }

        return KpiAssessment::query()->where('employee_id', $user->employee_id ?? 0);
    }

    private function ensureCanAssess(Request $request, KpiAssessment $assessment): void
    {
        $this->ensurePermission($request, 'kpi.assess');
        $user = $request->user();

        if (! $user->hasPermission('kpi.view.all')) {
            abort_unless(
                Employee::query()->visibleTo($user)->whereKey($assessment->employee_id)->exists(),
                403,
                'Pegawai ini di luar cakupan unit Anda.'
            );
        }
    }

    public function coachingIndex(Request $request): View
    {
        $query = CoachingRecord::query()->with(['employee:id,full_name,nip', 'coach:id,full_name']);
        $user = $request->user();

        if (! $user->hasPermission('coaching.view.all')) {
            if ($user->hasPermission('coaching.view.unit')) {
                $query->whereIn('employee_id', Employee::query()->visibleTo($user)->select('id'));
            } else {
                $query->where('employee_id', $user->employee_id ?? 0);
            }
        }

        return view('kpi.coaching.index', ['records' => $query->orderByDesc('date')->paginate(20)]);
    }

    public function storeCoaching(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'coaching.manage');

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'type' => 'required|in:konseling,pembinaan_kedisiplinan,teguran,evaluasi,pelatihan,penghargaan',
            'reason' => 'required|string|max:2000',
            'result' => 'nullable|string|max:2000',
        ]);

        $this->ensureRecordVisible($request, (int) $data['employee_id']);

        $record = CoachingRecord::query()->create($data + [
            'coach_id' => $request->user()->employee_id,
            'status' => 'draft',
        ]);

        $this->notifications->sendToPermission(
            'coaching.approve',
            'coaching',
            'Catatan pembinaan perlu persetujuan',
            'Catatan pembinaan untuk '.$record->employee?->full_name.' menunggu persetujuan.',
            ['coaching_record_id' => $record->id]
        );

        return back()->with('success', 'Catatan pembinaan tersimpan.');
    }

    public function approveCoaching(Request $request, CoachingRecord $record): RedirectResponse
    {
        $this->ensurePermission($request, 'coaching.approve');
        abort_unless($record->status === 'draft', 422, 'Catatan ini sudah diproses.');
        $record->update(['status' => 'disetujui']);

        if ($user = $record->employee?->user) {
            $this->notifications->send(
                $user,
                'coaching',
                'Catatan pembinaan disetujui',
                'Catatan pembinaan tanggal '.$record->date?->format('d/m/Y').' telah disetujui.',
                ['coaching_record_id' => $record->id]
            );
        }

        return back()->with('success', 'Catatan pembinaan disetujui.');
    }
}
