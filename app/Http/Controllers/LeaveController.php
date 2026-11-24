<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    use GuardsHrAccess;

    public function __construct(
        private readonly LeaveService $leave,
        private readonly AttendanceService $files
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyPermission(['leave.view.self', 'leave.view.unit', 'leave.view.all']),
            403
        );

        $query = LeaveRequest::query()
            ->with(['employee:id,nip,full_name,org_unit_id', 'employee.orgUnit:id,name', 'leaveType:id,code,name']);

        if ($user->hasPermission('leave.view.all')) {
            // seluruh data
        } elseif ($user->hasPermission('leave.view.unit')) {
            $query->whereIn('employee_id', Employee::query()->visibleTo($user)->select('id'));
        } else {
            $query->where('employee_id', $user->employee_id ?? 0);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', (int) $request->input('leave_type_id'));
        }

        if ($from = $request->input('from')) {
            $query->whereDate('end_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('start_date', '<=', $to);
        }

        return view('leave.index', [
            'requests' => $query->orderByDesc('start_date')->paginate(15)->withQueryString(),
            'statuses' => LeaveRequest::statusLabels(),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'),
            'canRequest' => $user->hasPermission('leave.request') && $user->employee_id,
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensurePermission($request, 'leave.request');
        $employee = $this->requireEmployee($request);

        return view('leave.form', [
            'employee' => $employee,
            'types' => LeaveType::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request, 'leave.request');
        $employee = $this->requireEmployee($request);

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'reason.min' => 'Alasan pengajuan minimal 10 karakter.',
        ]);

        if ($request->hasFile('document')) {
            $data['document_path'] = $this->files->storePhoto($request->file('document'), 'leave/documents');
        }

        $leave = $this->leave->create($employee, $data, $request->user());

        return redirect()->route('leave.show', $leave)
            ->with('success', 'Pengajuan izin/cuti dikirim.');
    }

    public function show(Request $request, LeaveRequest $leave): View
    {
        $this->ensureVisible($request, $leave);

        $leave->load([
            'employee.orgUnit', 'employee.position', 'leaveType', 'days',
        ]);

        $canApproveSupervisor = $request->user()->hasPermission('leave.approve.supervisor')
            && $request->user()->employee_id === $leave->employee->direct_supervisor_id
            && $leave->status === 'menunggu_atasan';

        return view('leave.show', [
            'leave' => $leave,
            'canApproveSupervisor' => $canApproveSupervisor,
            'canApproveHrd' => $request->user()->hasPermission('leave.approve.hrd')
                && $leave->status === 'menunggu_hrd',
            'canCancel' => $request->user()->employee_id === $leave->employee_id && $leave->isPending(),
        ]);
    }

    public function approveSupervisor(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->ensurePermission($request, 'leave.approve.supervisor');
        $this->ensureVisible($request, $leave);

        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->leave->approveBySupervisor($leave, $request->user(), $request->input('note'));

        return back()->with('success', 'Pengajuan disetujui dan diteruskan ke HRD.');
    }

    public function approveHrd(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->ensurePermission($request, 'leave.approve.hrd');
        $this->ensureVisible($request, $leave);

        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->leave->approveByHrd($leave, $request->user(), $request->input('note'));

        return back()->with('success', 'Pengajuan disetujui dan absensi diperbarui.');
    }

    public function reject(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless(
            $leave->status === 'menunggu_atasan'
                ? $request->user()->hasPermission('leave.approve.supervisor')
                : $request->user()->hasPermission('leave.approve.hrd'),
            403
        );

        $this->ensureVisible($request, $leave);

        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->leave->reject($leave, $request->user(), $request->input('reason'));

        return back()->with('success', 'Pengajuan ditolak.');
    }

    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->leave->cancel($leave, $request->user());

        return back()->with('success', 'Pengajuan dibatalkan.');
    }

    public function document(Request $request, LeaveRequest $leave): StreamedResponse
    {
        $this->ensureVisible($request, $leave);
        abort_unless(
            $leave->document_path && Storage::disk('local')->exists($leave->document_path),
            404,
            'Dokumen tidak ditemukan.'
        );

        return Storage::disk('local')->download($leave->document_path);
    }

    protected function ensureVisible(Request $request, LeaveRequest $leave): void
    {
        $user = $request->user();

        if ($user->employee_id === $leave->employee_id) {
            return;
        }

        if ($user->hasPermission('leave.view.all')) {
            return;
        }

        if ($user->hasPermission('leave.view.unit')
            && Employee::query()->visibleTo($user)->whereKey($leave->employee_id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke pengajuan ini.');
    }

    protected function requireEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'leave' => 'Akun Anda belum terhubung dengan data pegawai.',
            ]);
        }

        return $employee;
    }
}
