<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    use GuardsHrAccess;

    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'attendance.self');

        $employee = $request->user()->employee;

        if (! $employee) {
            return view('attendance.self', [
                'employee' => null,
                'attendance' => null,
                'resolved' => null,
                'location' => null,
                'recent' => collect(),
                'checkInKey' => null,
                'checkOutKey' => null,
                'maxPhotoKb' => (int) Setting::get('attendance.max_photo_kb', 2048),
            ]);
        }

        $today = now('Asia/Jakarta')->toDateString();

        $attendance = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        $recent = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '<', $today)
            ->orderByDesc('work_date')
            ->limit(7)
            ->get();

        return view('attendance.self', [
            'employee' => $employee->load('workLocation', 'orgUnit', 'position'),
            'attendance' => $attendance,
            'resolved' => $resolved = $this->attendance->resolveSchedule($employee, $today),
            'location' => $resolved['location'] ?? $employee->workLocation,
            'recent' => $recent,
            'checkInKey' => (string) Str::uuid(),
            'checkOutKey' => (string) Str::uuid(),
            'maxPhotoKb' => (int) Setting::get('attendance.max_photo_kb', 2048),
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.self');

        $employee = $this->requireEmployee($request);
        $data = $this->validatedPunch($request);
        $data['photo_path'] = $this->attendance->storePhoto($request->file('photo'), 'attendance/check-in');
        $data['ip'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        $attendance = $this->attendance->checkIn($employee, $data, $request->user());

        return redirect()->route('attendance.index')->with(
            'success',
            'Presensi masuk tercatat pukul '.$attendance->checkInLocal()?->format('H:i').'.'
        );
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.self');

        $employee = $this->requireEmployee($request);
        $data = $this->validatedPunch($request);
        $data['photo_path'] = $this->attendance->storePhoto($request->file('photo'), 'attendance/check-out');
        $data['ip'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        $attendance = $this->attendance->checkOut($employee, $data, $request->user());

        return redirect()->route('attendance.index')->with(
            'success',
            'Presensi pulang tercatat pukul '.$attendance->checkOutLocal()?->format('H:i').'.'
        );
    }

    public function history(Request $request): View
    {
        $this->ensurePermission($request, 'attendance.view.self');
        $employee = $this->requireEmployee($request);

        $month = (string) $request->input('month', now('Asia/Jakarta')->format('Y-m'));
        $from = $month.'-01';
        $to = date('Y-m-t', strtotime($from));

        $rows = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->orderByDesc('work_date')
            ->paginate(31)
            ->withQueryString();

        return view('attendance.history', [
            'employee' => $employee,
            'rows' => $rows,
            'month' => $month,
            'summary' => $this->summaryFor($employee->id, $from, $to),
        ]);
    }

    public function show(Request $request, Attendance $attendance): View
    {
        $this->ensurePermission($request, 'attendance.view.self');
        $this->ensureRecordVisible($request, $attendance->employee_id);

        $attendance->load(['employee.orgUnit', 'employee.position', 'shift', 'corrections.supervisor', 'corrections.hrd']);

        return view('attendance.show', ['attendance' => $attendance]);
    }

    public function photo(Request $request, Attendance $attendance, string $type): StreamedResponse
    {
        $this->ensurePermission($request, 'attendance.view.self');
        $this->ensureRecordVisible($request, $attendance->employee_id);

        abort_unless(in_array($type, ['in', 'out'], true), 404);

        $path = $type === 'in' ? $attendance->check_in_photo_path : $attendance->check_out_photo_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Foto tidak ditemukan.');

        return Storage::disk('local')->response($path);
    }

    public function report(Request $request): View
    {
        $this->ensurePermission($request, 'attendance.report');

        $from = (string) $request->input('from', now('Asia/Jakarta')->startOfMonth()->toDateString());
        $to = (string) $request->input('to', now('Asia/Jakarta')->toDateString());

        $query = Attendance::query()
            ->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name', 'shift:id,name'])
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to);

        $this->scopeEmployeeRecords($query, $request->user());

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $summary = (clone $query)
            ->reorder()
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'pulang_awal' THEN 1 ELSE 0 END) as pulang_awal,
                SUM(CASE WHEN status = 'tidak_hadir' THEN 1 ELSE 0 END) as tidak_hadir,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi,
                SUM(CASE WHEN status = 'libur' THEN 1 ELSE 0 END) as libur,
                COALESCE(SUM(late_minutes), 0) as total_late_minutes,
                COALESCE(SUM(early_leave_minutes), 0) as total_early_minutes,
                COALESCE(SUM(work_minutes), 0) as total_work_minutes")
            ->first();

        return view('attendance.report', [
            'rows' => $query->orderByDesc('work_date')->orderBy('employee_id')->paginate(31)->withQueryString(),
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'employees' => Employee::query()->visibleTo($request->user())->orderBy('full_name')->pluck('full_name', 'id'),
            'statuses' => Attendance::statusLabels(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedPunch(Request $request): array
    {
        $maxKb = (int) Setting::get('attendance.max_photo_kb', 2048);

        return $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['required', 'numeric', 'min:0', 'max:100000'],
            'idempotency_key' => ['nullable', 'uuid'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ], [
            'photo.required' => 'Foto selfie wajib diambil untuk presensi mandiri.',
            'photo.image' => 'Berkas foto tidak valid.',
        ]);
    }

    protected function requireEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'attendance' => 'Akun Anda belum terhubung dengan data pegawai.',
            ]);
        }

        return $employee;
    }

    /**
     * @return object
     */
    protected function summaryFor(int $employeeId, string $from, string $to): object
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'pulang_awal' THEN 1 ELSE 0 END) as pulang_awal,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'tidak_hadir' THEN 1 ELSE 0 END) as tidak_hadir,
                SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi,
                COALESCE(SUM(late_minutes), 0) as total_late_minutes,
                COALESCE(SUM(work_minutes), 0) as total_work_minutes")
            ->first();
    }
}
