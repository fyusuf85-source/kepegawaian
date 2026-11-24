<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\AttendanceException;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExceptionController extends Controller
{
    use GuardsHrAccess;

    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasPermission('attendance.exception.manage') || $user->hasPermission('attendance.self'),
            403
        );

        $query = AttendanceException::query()
            ->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name', 'approver:id,name']);

        if ($user->hasPermission('attendance.exception.manage')) {
            $this->scopeEmployeeRecords($query, $user);
        } else {
            $query->where('employee_id', $user->employee_id ?? 0);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('attendance.exceptions.index', [
            'exceptions' => $query->orderByDesc('work_date')->paginate(15)->withQueryString(),
            'statuses' => AttendanceException::statusLabels(),
            'types' => AttendanceException::typeLabels(),
            'canManage' => $user->hasPermission('attendance.exception.manage'),
            'canRequest' => $user->hasPermission('attendance.self') && $user->employee_id,
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'attendance.self');
        $employee = $this->requireEmployee($request);

        return view('attendance.exceptions.form', [
            'employee' => $employee,
            'workDate' => (string) $request->input('work_date', now('Asia/Jakarta')->toDateString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.self');
        $employee = $this->requireEmployee($request);

        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(AttendanceException::typeLabels()))],
            'check_in_at' => ['nullable', 'date_format:H:i'],
            'check_out_at' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'reason.min' => 'Alasan dispensasi minimal 10 karakter.',
        ]);

        if ($data['work_date'] > now('Asia/Jakarta')->toDateString()) {
            throw ValidationException::withMessages([
                'work_date' => 'Tanggal dispensasi tidak boleh melebihi hari ini.',
            ]);
        }

        if (! filled($data['check_in_at'] ?? null) && ! filled($data['check_out_at'] ?? null)) {
            throw ValidationException::withMessages([
                'check_in_at' => 'Isi minimal jam masuk atau jam pulang yang diajukan.',
            ]);
        }

        if ($request->hasFile('evidence')) {
            $data['evidence_path'] = $this->attendance->storePhoto($request->file('evidence'), 'attendance/exceptions');
        }

        $exception = $this->attendance->submitException($employee, $data, $request->user());

        return redirect()->route('exceptions.index')
            ->with('success', 'Pengajuan dispensasi absensi dikirim (menunggu persetujuan).');
    }

    public function approve(Request $request, AttendanceException $exception): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.exception.manage');
        $this->attendance->approveException($exception, $request->user());

        return back()->with('success', 'Dispensasi disetujui dan absensi diperbarui.');
    }

    public function reject(Request $request, AttendanceException $exception): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.exception.manage');
        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        $this->attendance->rejectException($exception, $request->user(), $request->input('reason'));

        return back()->with('success', 'Dispensasi ditolak.');
    }

    public function evidence(Request $request, AttendanceException $exception): StreamedResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasPermission('attendance.exception.manage')
                || $user->employee_id === $exception->employee_id,
            403
        );

        if ($user->hasPermission('attendance.exception.manage')) {
            $this->ensureRecordVisible($request, $exception->employee_id);
        }

        abort_unless(
            $exception->evidence_path && Storage::disk('local')->exists($exception->evidence_path),
            404,
            'Bukti tidak ditemukan.'
        );

        return Storage::disk('local')->download($exception->evidence_path);
    }

    protected function requireEmployee(Request $request): \App\Models\Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'exception' => 'Akun Anda belum terhubung dengan data pegawai.',
            ]);
        }

        return $employee;
    }
}
