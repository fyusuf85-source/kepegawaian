<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDay;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly NotificationService $notifications
    ) {
    }

    /**
     * Hari kerja efektif dalam rentang tanggal (mengikuti jadwal dan hari libur).
     *
     * @return array<int, string>
     */
    public function workingDays(Employee $employee, string $start, string $end): array
    {
        $days = [];
        $cursor = Carbon::parse($start, 'Asia/Jakarta');
        $last = Carbon::parse($end, 'Asia/Jakarta');

        while ($cursor->lte($last)) {
            $date = $cursor->toDateString();
            $resolved = $this->attendance->resolveSchedule($employee, $date);

            if ($resolved['is_workday']) {
                $days[] = $date;
            }

            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data, User $actor): LeaveRequest
    {
        $type = LeaveType::query()->whereKey($data['leave_type_id'])->where('is_active', true)->firstOrFail();
        $start = (string) $data['start_date'];
        $end = (string) $data['end_date'];

        if ($end < $start) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]);
        }

        $overlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['menunggu_atasan', 'menunggu_hrd', 'disetujui'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Terdapat pengajuan izin/cuti lain pada rentang tanggal tersebut.',
            ]);
        }

        $days = $this->workingDays($employee, $start, $end);

        if ($days === []) {
            throw ValidationException::withMessages([
                'start_date' => 'Tidak ada hari kerja efektif pada rentang tanggal tersebut.',
            ]);
        }

        $this->assertDocumentRequirement($type, count($days), $data['document_path'] ?? null);
        $this->assertBalance($employee, $type, $days);

        $supervisorUser = $employee->direct_supervisor_id
            ? User::query()->where('employee_id', $employee->direct_supervisor_id)->where('is_active', true)->first()
            : null;

        return DB::transaction(function () use ($employee, $type, $start, $end, $days, $data, $supervisorUser): LeaveRequest {
            $request = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $start,
                'end_date' => $end,
                'total_days' => count($days),
                'reason' => $data['reason'],
                'document_path' => $data['document_path'] ?? null,
                'status' => $supervisorUser ? 'menunggu_atasan' : 'menunggu_hrd',
                'current_step' => $supervisorUser ? 1 : 2,
                'submitted_at' => now(),
            ]);

            foreach ($days as $date) {
                $request->days()->create(['date' => $date]);
            }

            if ($supervisorUser) {
                $this->notifications->send(
                    $supervisorUser,
                    'leave',
                    'Pengajuan izin/cuti menunggu',
                    $employee->full_name.' mengajukan '.$type->name.' tanggal '.$start.' s.d. '.$end.'.',
                    ['leave_request_id' => $request->id, 'dedupe_key' => 'leave-'.$request->id.'-supervisor']
                );
            } else {
                $this->notifyHrd($request);
            }

            return $request;
        });
    }

    public function approveBySupervisor(LeaveRequest $request, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $actor): void {
            $request = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== 'menunggu_atasan') {
                throw ValidationException::withMessages([
                    'leave' => 'Pengajuan tidak berada pada tahap persetujuan atasan.',
                ]);
            }

            if ($actor->employee_id !== $request->employee->direct_supervisor_id) {
                throw ValidationException::withMessages([
                    'leave' => 'Hanya atasan langsung pegawai yang dapat menyetujui tahap ini.',
                ]);
            }

            $request->update([
                'status' => 'menunggu_hrd',
                'current_step' => 2,
            ]);

            $this->notifyHrd($request->fresh('employee', 'leaveType'));
        });
    }

    public function approveByHrd(LeaveRequest $request, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $actor): void {
            $request = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== 'menunggu_hrd') {
                throw ValidationException::withMessages([
                    'leave' => 'Pengajuan tidak berada pada tahap verifikasi HRD.',
                ]);
            }

            $days = $request->days->pluck('date')->map->toDateString()->all();
            $this->assertBalance($request->employee, $request->leaveType, $days, $request->id);
            $this->applyLeaveAttendance($request);

            $request->update([
                'status' => 'disetujui',
                'decided_at' => now(),
            ]);

            $this->applyBalance($request);

            $this->notifyEmployee(
                $request->employee,
                'Pengajuan izin/cuti disetujui',
                $request->leaveType->name.' tanggal '.$request->start_date->toDateString()
                    .' s.d. '.$request->end_date->toDateString().' telah disetujui.'
            );
        });
    }

    public function reject(LeaveRequest $request, User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        DB::transaction(function () use ($request, $actor, $reason): void {
            $request = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! $request->isPending()) {
                throw ValidationException::withMessages(['leave' => 'Pengajuan sudah selesai diproses.']);
            }

            if ($request->status === 'menunggu_atasan') {
                if ($actor->employee_id !== $request->employee->direct_supervisor_id) {
                    throw ValidationException::withMessages([
                        'leave' => 'Hanya atasan langsung yang dapat menolak pada tahap ini.',
                    ]);
                }
            } elseif (! $actor->hasPermission('leave.approve.hrd')) {
                throw ValidationException::withMessages([
                    'leave' => 'Anda tidak berwenang menolak pada tahap HRD.',
                ]);
            }

            $request->update([
                'status' => 'ditolak',
                'rejection_reason' => $reason,
                'decided_at' => now(),
            ]);

            $this->notifyEmployee(
                $request->employee,
                'Pengajuan izin/cuti ditolak',
                $request->leaveType->name.' tanggal '.$request->start_date->toDateString()
                    .' s.d. '.$request->end_date->toDateString().' ditolak: '.$reason
            );
        });
    }

    public function cancel(LeaveRequest $request, User $actor): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['leave' => 'Hanya pengajuan yang masih menunggu dapat dibatalkan.']);
        }

        if ($actor->employee_id !== $request->employee_id && ! $actor->hasRole('administrator')) {
            throw ValidationException::withMessages(['leave' => 'Hanya pengaju yang dapat membatalkan pengajuan.']);
        }

        $request->update(['status' => 'dibatalkan']);
    }

    public function balanceFor(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ],
            [
                'entitlement_days' => $type->max_days_per_year ?? 0,
                'used_days' => 0,
                'adjustment_days' => 0,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * @param  array<int, string>  $days
     */
    protected function assertBalance(Employee $employee, LeaveType $type, array $days, ?int $excludeRequestId = null): void
    {
        if (! $type->affects_balance) {
            return;
        }

        foreach ($this->daysByYear($days) as $year => $count) {
            $balance = $this->balanceFor($employee, $type, $year);
            $pending = $this->pendingDays($employee, $type, $year, $excludeRequestId);
            $remaining = $balance->remainingDays() - $pending;

            if ($remaining + 0.0001 < $count) {
                throw ValidationException::withMessages([
                    'leave' => 'Saldo '.$type->name.' tahun '.$year.' tidak mencukupi. '
                        .'Sisa '.rtrim(rtrim(number_format($remaining, 1, ',', '.'), '0'), ',').' hari, '
                        .'diajukan '.$count.' hari.',
                ]);
            }
        }
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, int>
     */
    protected function daysByYear(array $days): array
    {
        $byYear = [];

        foreach ($days as $date) {
            $year = (int) substr((string) $date, 0, 4);
            $byYear[$year] = ($byYear[$year] ?? 0) + 1;
        }

        return $byYear;
    }

    protected function pendingDays(Employee $employee, LeaveType $type, int $year, ?int $excludeRequestId): int
    {
        return (int) LeaveRequestDay::query()
            ->whereYear('date', $year)
            ->whereHas('leaveRequest', function ($query) use ($employee, $type, $excludeRequestId): void {
                $query->where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)
                    ->whereIn('status', ['menunggu_atasan', 'menunggu_hrd', 'disetujui'])
                    ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId));
            })
            ->count();
    }

    protected function applyBalance(LeaveRequest $request): void
    {
        if (! $request->leaveType->affects_balance) {
            return;
        }

        $days = $request->days->pluck('date')->map->toDateString()->all();

        foreach ($this->daysByYear($days) as $year => $count) {
            $balance = $this->balanceFor($request->employee, $request->leaveType, $year);
            $balance->update([
                'used_days' => (float) $balance->used_days + $count,
                'updated_at' => now(),
            ]);
        }
    }

    protected function applyLeaveAttendance(LeaveRequest $request): void
    {
        $status = match ($request->leaveType->code) {
            'sakit' => 'sakit',
            'dinas' => 'dispensasi',
            default => 'izin',
        };

        $prepared = [];

        foreach ($request->days as $day) {
            $date = $day->date->toDateString();
            $resolved = $this->attendance->resolveSchedule($request->employee, $date);

            if (! $resolved['shift']) {
                throw ValidationException::withMessages([
                    'leave' => 'Shift kerja pada tanggal '.$date.' tidak ditemukan.',
                ]);
            }

            $row = Attendance::query()
                ->where('employee_id', $request->employee_id)
                ->whereDate('work_date', $date)
                ->where('shift_id', $resolved['shift']->id)
                ->lockForUpdate()
                ->first();

            if ($row?->is_locked) {
                throw ValidationException::withMessages([
                    'leave' => 'Absensi tanggal '.$date.' sudah dikunci payroll; pengajuan tidak dapat diterapkan.',
                ]);
            }

            $prepared[] = [$date, $resolved, $row];
        }

        foreach ($prepared as [$date, $resolved, $row]) {
            if (! $row) {
                $row = new Attendance([
                    'employee_id' => $request->employee_id,
                    'work_date' => $date,
                    'shift_id' => $resolved['shift']->id,
                    'schedule_assignment_id' => $resolved['assignment']?->id,
                ]);
            }

            $row->fill([
                'check_in_at' => null,
                'check_out_at' => null,
                'check_in_photo_path' => null,
                'check_out_photo_path' => null,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'work_minutes' => 0,
                'status' => $status,
                'source' => 'system',
                'notes' => 'Izin/cuti #'.$request->id.' ('.$request->leaveType->code.')',
            ]);

            $row->save();
        }
    }

    protected function assertDocumentRequirement(LeaveType $type, int $totalDays, ?string $documentPath): void
    {
        if (! $type->requires_document) {
            return;
        }

        $threshold = $type->document_required_after_days;

        if ($threshold !== null && $totalDays <= $threshold) {
            return;
        }

        if (! $documentPath) {
            throw ValidationException::withMessages([
                'document' => 'Dokumen pendukung wajib diunggah untuk '.$type->name
                    .($threshold ? ' lebih dari '.$threshold.' hari' : '').'.',
            ]);
        }
    }

    protected function notifyHrd(LeaveRequest $request): void
    {
        $this->notifications->sendToPermission(
            'leave.approve.hrd',
            'leave',
            'Pengajuan izin/cuti menunggu verifikasi HRD',
            $request->employee->full_name.' — '.$request->leaveType->name.' '
                .$request->start_date->toDateString().' s.d. '.$request->end_date->toDateString().'.',
            [
                'leave_request_id' => $request->id,
                'dedupe_key' => 'leave-'.$request->id.'-hrd',
            ]
        );
    }

    protected function notifyEmployee(Employee $employee, string $title, string $message): void
    {
        if ($employee->user) {
            $this->notifications->send($employee->user, 'leave', $title, $message, [
                'employee_id' => $employee->id,
            ]);
        }
    }
}
