<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\WorkSchedule;
use App\Models\WorkShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensure($request, 'schedule.view');

        $schedules = WorkSchedule::query()
            ->with(['orgUnit:id,name'])
            ->withCount(['days', 'assignments'])
            ->orderByDesc('effective_from')
            ->paginate(15);

        return view('schedules.index', [
            'schedules' => $schedules,
            'canManage' => $request->user()->hasPermission('schedule.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensure($request, 'schedule.manage');

        return view('schedules.form', [
            'schedule' => new WorkSchedule([
                'effective_from' => now('Asia/Jakarta')->toDateString(),
                'is_active' => true,
            ]),
            'days' => collect(range(1, 7))->mapWithKeys(fn ($day) => [$day => null]),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensure($request, 'schedule.manage');

        $data = $this->validated($request);
        $days = $data['days'];
        unset($data['days']);

        $schedule = WorkSchedule::create($data);
        $this->syncDays($schedule, $days);

        return redirect()->route('schedules.index')->with('success', 'Jadwal kerja berhasil dibuat.');
    }

    public function edit(Request $request, WorkSchedule $schedule): View
    {
        $this->ensure($request, 'schedule.manage');

        $schedule->load('days');

        return view('schedules.form', [
            'schedule' => $schedule,
            'days' => $schedule->days->keyBy('day_of_week'),
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, WorkSchedule $schedule): RedirectResponse
    {
        $this->ensure($request, 'schedule.manage');

        $data = $this->validated($request);
        $days = $data['days'];
        unset($data['days']);

        $schedule->update($data);
        $schedule->days()->delete();
        $this->syncDays($schedule, $days);

        return redirect()->route('schedules.index')->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(Request $request, WorkSchedule $schedule): RedirectResponse
    {
        $this->ensure($request, 'schedule.manage');

        if ($schedule->assignments()->exists()) {
            return back()->withErrors(['delete' => 'Jadwal masih ditugaskan ke pegawai.']);
        }

        $schedule->delete();

        return redirect()->route('schedules.index')->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    protected function ensure(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()->hasPermission($permission),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'org_unit_id' => ['nullable', 'exists:org_units,id'],
            'employee_category' => ['nullable', Rule::in(['tetap', 'harian', 'khidmah'])],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'days' => ['required', 'array'],
            'days.*.is_workday' => ['nullable', 'boolean'],
            'days.*.shift_id' => ['nullable', 'exists:work_shifts,id'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $data['days'] = collect(range(1, 7))->mapWithKeys(function ($day) use ($request) {
            $isWorkday = $request->boolean("days.{$day}.is_workday");

            return [$day => [
                'is_workday' => $isWorkday,
                'shift_id' => $isWorkday ? ($request->input("days.{$day}.shift_id") ?: null) : null,
            ]];
        })->all();

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $days
     */
    protected function syncDays(WorkSchedule $schedule, array $days): void
    {
        foreach ($days as $day => $values) {
            $schedule->days()->create([
                'day_of_week' => $day,
                'shift_id' => $values['shift_id'],
                'is_workday' => $values['is_workday'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [
            'orgUnits' => OrgUnit::query()->where('is_active', true)->orderBy('level')->orderBy('name')->pluck('name', 'id'),
            'shifts' => WorkShift::query()->where('is_active', true)->orderBy('start_time')->get(),
        ];
    }
}
