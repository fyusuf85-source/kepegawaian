<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorrectionController extends Controller
{
    use GuardsHrAccess;

    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'attendance.correction.request',
                'attendance.correction.approve.supervisor',
                'attendance.correction.approve.hrd',
            ]),
            403
        );

        $query = AttendanceCorrection::query()
            ->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name', 'supervisor:id,full_name']);

        $user = $request->user();

        if ($user->hasPermission('attendance.correction.approve.hrd')) {
            $this->scopeEmployeeRecords($query, $user);
        } elseif ($user->hasPermission('attendance.correction.approve.supervisor')) {
            $query->where(fn ($q) => $q->where('supervisor_id', $user->employee_id)
                ->orWhere('employee_id', $user->employee_id));
        } else {
            $query->where('employee_id', $user->employee_id ?? 0);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('work_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('work_date', '<=', $to);
        }

        return view('attendance.corrections.index', [
            'corrections' => $query->orderByDesc('created_at')->paginate(15)->withQueryString(),
            'statuses' => AttendanceCorrection::statusLabels(),
            'canRequest' => $user->hasPermission('attendance.correction.request') && $user->employee_id,
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'attendance.correction.request');
        $employee = $this->requireEmployee($request);

        $attendance = null;

        if ($request->filled('attendance_id')) {
            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->findOrFail((int) $request->input('attendance_id'));
        }

        return view('attendance.corrections.form', [
            'employee' => $employee,
            'attendance' => $attendance,
            'workDate' => $attendance?->work_date?->toDateString()
                ?? (string) $request->input('work_date', now('Asia/Jakarta')->toDateString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.correction.request');
        $employee = $this->requireEmployee($request);

        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'correction_type' => ['required', Rule::in(['masuk', 'pulang', 'keduanya', 'tidak_hadir'])],
            'requested_check_in_at' => ['nullable', 'date_format:H:i', 'required_if:correction_type,masuk,keduanya'],
            'requested_check_out_at' => ['nullable', 'date_format:H:i', 'required_if:correction_type,pulang,keduanya'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'reason.min' => 'Alasan koreksi minimal 10 karakter.',
            'requested_check_in_at.required_if' => 'Jam masuk yang diminta wajib diisi.',
            'requested_check_out_at.required_if' => 'Jam pulang yang diminta wajib diisi.',
        ]);

        if ($data['work_date'] > now('Asia/Jakarta')->toDateString()) {
            throw ValidationException::withMessages([
                'work_date' => 'Tanggal koreksi tidak boleh melebihi hari ini.',
            ]);
        }

        if ($request->hasFile('evidence')) {
            $data['evidence_path'] = $this->attendance->storePhoto($request->file('evidence'), 'attendance/corrections');
        }

        $correction = $this->attendance->submitCorrection($employee, $data, $request->user());

        return redirect()->route('corrections.show', $correction)
            ->with('success', 'Pengajuan koreksi absensi dikirim.');
    }

    public function show(Request $request, AttendanceCorrection $correction): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'attendance.correction.request',
                'attendance.correction.approve.supervisor',
                'attendance.correction.approve.hrd',
            ]),
            403
        );

        $this->ensureVisible($request, $correction);

        $correction->load(['employee.orgUnit', 'employee.position', 'attendance.shift', 'supervisor', 'hrd']);

        return view('attendance.corrections.show', [
            'correction' => $correction,
            'canApproveSupervisor' => $request->user()->hasPermission('attendance.correction.approve.supervisor')
                && $request->user()->employee_id === $correction->supervisor_id
                && $correction->status === 'menunggu_atasan',
            'canApproveHrd' => $request->user()->hasPermission('attendance.correction.approve.hrd')
                && $correction->status === 'menunggu_hrd',
            'canCancel' => $request->user()->employee_id === $correction->employee_id
                && in_array($correction->status, ['menunggu_atasan', 'menunggu_hrd'], true),
        ]);
    }

    public function approveSupervisor(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.correction.approve.supervisor');
        $this->ensureVisible($request, $correction);

        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->attendance->approveCorrectionBySupervisor($correction, $request->user(), $request->input('note'));

        return back()->with('success', 'Koreksi disetujui dan diteruskan ke HRD.');
    }

    public function approveHrd(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->ensurePermission($request, 'attendance.correction.approve.hrd');
        $this->ensureVisible($request, $correction);

        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->attendance->approveCorrectionByHrd($correction, $request->user(), $request->input('note'));

        return back()->with('success', 'Koreksi disetujui dan diterapkan pada absensi.');
    }

    public function reject(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        abort_unless(
            $correction->status === 'menunggu_atasan'
                ? $request->user()->hasPermission('attendance.correction.approve.supervisor')
                : $request->user()->hasPermission('attendance.correction.approve.hrd'),
            403
        );

        $this->ensureVisible($request, $correction);

        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->attendance->rejectCorrection($correction, $request->user(), $request->input('reason'));

        return back()->with('success', 'Koreksi ditolak.');
    }

    public function cancel(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->attendance->cancelCorrection($correction, $request->user());

        return back()->with('success', 'Koreksi dibatalkan.');
    }

    public function evidence(Request $request, AttendanceCorrection $correction): StreamedResponse
    {
        $this->ensureVisible($request, $correction);
        abort_unless(
            $correction->evidence_path && Storage::disk('local')->exists($correction->evidence_path),
            404,
            'Bukti tidak ditemukan.'
        );

        return Storage::disk('local')->download($correction->evidence_path);
    }

    protected function ensureVisible(Request $request, AttendanceCorrection $correction): void
    {
        $user = $request->user();

        if ($user->employee_id === $correction->employee_id) {
            return;
        }

        if ($user->employee_id && $user->employee_id === $correction->supervisor_id) {
            return;
        }

        if ($user->hasPermission('attendance.correction.approve.hrd')) {
            $this->ensureRecordVisible($request, $correction->employee_id);

            return;
        }

        abort(403, 'Anda tidak memiliki akses ke pengajuan koreksi ini.');
    }

    protected function requireEmployee(Request $request): \App\Models\Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'correction' => 'Akun Anda belum terhubung dengan data pegawai.',
            ]);
        }

        return $employee;
    }
}
