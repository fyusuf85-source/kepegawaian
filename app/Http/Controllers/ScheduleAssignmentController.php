<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('schedule.view'), 403);

        $assignments = ScheduleAssignment::query()
            ->with(['employee:id,full_name,nip', 'workSchedule:id,name', 'workLocation:id,name'])
            ->orderByDesc('effective_from')
            ->paginate(20);

        return view('schedules.assignments', [
            'assignments' => $assignments,
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
            'schedules' => WorkSchedule::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'locations' => WorkLocation::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'canManage' => $request->user()->hasPermission('schedule.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('schedule.manage'), 403);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_schedule_id' => ['required', 'exists:work_schedules,id'],
            'work_location_id' => ['nullable', 'exists:work_locations,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $overlap = ScheduleAssignment::query()
            ->where('employee_id', $data['employee_id'])
            ->where(function ($q) use ($data) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $data['effective_from']);
            })
            ->when(
                $data['effective_to'] ?? null,
                fn ($q, $end) => $q->where('effective_from', '<=', $end)
            )
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => 'Pegawai sudah memiliki jadwal pada rentang tanggal tersebut.',
            ]);
        }

        ScheduleAssignment::create($data + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Jadwal pegawai berhasil ditetapkan.');
    }

    public function destroy(Request $request, ScheduleAssignment $assignment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('schedule.manage'), 403);

        $assignment->delete();

        return back()->with('success', 'Penugasan jadwal berhasil dihapus.');
    }
}
