<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceException;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\ScheduleAssignment;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use App\Models\WorkShift;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Jadwal dan shift yang berlaku untuk pegawai pada tanggal tertentu.
     *
     * @return array{assignment: ?ScheduleAssignment, schedule: ?WorkSchedule, day: mixed, shift: ?WorkShift, is_workday: bool}
     */
    public function resolveSchedule(Employee $employee, string $date): array
    {
        $dayOfWeek = Carbon::parse($date, 'Asia/Jakarta')->dayOfWeekIso;

        $assignment = ScheduleAssignment::query()
            ->with(['workSchedule.days.shift', 'workLocation'])
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')
                ->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first();

        $schedule = $assignment?->workSchedule;

        if (! $schedule) {
            $schedule = WorkSchedule::query()
                ->with('days.shift')
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $date)
                ->where(fn ($query) => $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date))
                ->where(fn ($query) => $query->where('org_unit_id', $employee->org_unit_id)
                    ->orWhereNull('org_unit_id'))
                ->orderByRaw('org_unit_id IS NULL')
                ->first();
        }

        $day = $schedule?->days->firstWhere('day_of_week', $dayOfWeek);
        $shift = $day?->shift;

        $holiday = Holiday::query()->whereDate('date', $date)->exists();
        $isWorkday = ! $holiday && $day?->is_workday && $shift;

        return [
            'assignment' => $assignment,
            'schedule' => $schedule,
            'day' => $day,
            'shift' => $shift,
            'location' => $assignment?->workLocation ?? $employee->workLocation,
            'is_workday' => (bool) $isWorkday,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function checkIn(Employee $employee, array $data, User $actor): Attendance
    {
        $date = now('Asia/Jakarta')->toDateString();
        $resolved = $this->resolveSchedule($employee, $date);

        if (! $resolved['is_workday'] || ! $resolved['shift'] instanceof WorkShift) {
            throw ValidationException::withMessages([
                'attendance' => 'Hari ini bukan hari kerja sesuai jadwal Anda.',
            ]);
        }

        $shift = $resolved['shift'];
        $location = $resolved['location'] ?? $employee->workLocation;
        $this->assertLocation($location);

        $accuracy = (float) ($data['accuracy_m'] ?? 0);

        if ($accuracy <= 0) {
            throw ValidationException::withMessages([
                'attendance' => 'Akurasi GPS tidak terbaca. Gunakan dispensasi bila GPS bermasalah.',
            ]);
        }

        $threshold = $location->accuracy_threshold_m
            ?: (int) Setting::get('attendance.accuracy_threshold_m', 100);

        if ($accuracy > $threshold) {
            throw ValidationException::withMessages([
                'attendance' => 'Akurasi GPS '.round($accuracy).' m melebihi batas '.$threshold.' m. Ulangi di tempat terbuka atau ajukan dispensasi.',
            ]);
        }

        $distance = $this->haversine(
            (float) $data['lat'],
            (float) $data['lng'],
            (float) $location->latitude,
            (float) $location->longitude
        );

        if ($distance > (float) $location->radius_m) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda berada '.round($distance).' m dari '.$location->name
                    .' (radius '.$location->radius_m.' m). Presensi ditolak; ajukan dispensasi bila ada penugasan luar.',
            ]);
        }

        $key = $this->idempotencyKey($data['idempotency_key'] ?? null);

        return DB::transaction(function () use ($employee, $date, $shift, $resolved, $data, $distance, $key): Attendance {
            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $date)
                ->where('shift_id', $shift->id)
                ->lockForUpdate()
                ->first();

            if ($attendance?->is_locked) {
                throw ValidationException::withMessages([
                    'attendance' => 'Data absensi hari ini sudah dikunci payroll dan tidak dapat diubah.',
                ]);
            }

            if ($attendance && $attendance->check_in_at) {
                if ($attendance->check_in_idempotency_key === $key) {
                    return $attendance;
                }

                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah melakukan presensi masuk hari ini pada '
                        .$attendance->checkInLocal()?->format('H:i').'.',
                ]);
            }

            if (! $attendance) {
                $location = $resolved['location'] ?? $employee->workLocation;
                $attendance = new Attendance([
                    'employee_id' => $employee->id,
                    'work_date' => $date,
                    'shift_id' => $shift->id,
                    'schedule_assignment_id' => $resolved['assignment']?->id,
                    'work_location_id' => $location?->id,
                ]);
            }

            $at = now();

            $attendance->fill([
                'check_in_at' => $at,
                'check_in_lat' => $data['lat'],
                'check_in_lng' => $data['lng'],
                'check_in_accuracy_m' => $data['accuracy_m'],
                'check_in_distance_m' => round($distance, 2),
                'check_in_photo_path' => $data['photo_path'],
                'check_in_ip' => $data['ip'] ?? null,
                'check_in_user_agent' => isset($data['user_agent']) ? substr((string) $data['user_agent'], 0, 255) : null,
                'check_in_idempotency_key' => $key,
                'source' => 'self',
                'work_location_id' => $location?->id,
            ]);

            $attendance->late_minutes = $this->lateMinutes($attendance, $shift);
            $attendance->status = $attendance->late_minutes > 0 ? 'terlambat' : 'hadir';
            $attendance->save();

            return $attendance;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function checkOut(Employee $employee, array $data, User $actor): Attendance
    {
        $attendance = $this->activeAttendanceForCheckOut($employee);

        if (! $attendance || ! $attendance->shift instanceof WorkShift) {
            throw ValidationException::withMessages([
                'attendance' => 'Presensi masuk belum tercatat hari ini.',
            ]);
        }

        $shift = $attendance->shift;
        $location = $attendance->workLocation ?? $employee->workLocation;
        $this->assertLocation($location);

        $accuracy = (float) ($data['accuracy_m'] ?? 0);

        if ($accuracy <= 0) {
            throw ValidationException::withMessages([
                'attendance' => 'Akurasi GPS tidak terbaca. Gunakan dispensasi bila GPS bermasalah.',
            ]);
        }

        $threshold = $location->accuracy_threshold_m
            ?: (int) Setting::get('attendance.accuracy_threshold_m', 100);

        if ($accuracy > $threshold) {
            throw ValidationException::withMessages([
                'attendance' => 'Akurasi GPS '.round($accuracy).' m melebihi batas '.$threshold.' m.',
            ]);
        }

        $distance = $this->haversine(
            (float) $data['lat'],
            (float) $data['lng'],
            (float) $location->latitude,
            (float) $location->longitude
        );

        if ($distance > (float) $location->radius_m) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda berada '.round($distance).' m dari '.$location->name
                    .' (radius '.$location->radius_m.' m). Presensi pulang ditolak; ajukan dispensasi bila perlu.',
            ]);
        }

        $key = $this->idempotencyKey($data['idempotency_key'] ?? null);

        return DB::transaction(function () use ($attendance, $shift, $data, $distance, $key): Attendance {
            $attendance = Attendance::query()
                ->lockForUpdate()
                ->findOrFail($attendance->id);

            if (! $attendance->check_in_at) {
                throw ValidationException::withMessages([
                    'attendance' => 'Presensi masuk belum tercatat.',
                ]);
            }

            if ($attendance->is_locked) {
                throw ValidationException::withMessages([
                    'attendance' => 'Data absensi hari ini sudah dikunci payroll dan tidak dapat diubah.',
                ]);
            }

            if ($attendance->check_out_at) {
                if ($attendance->check_out_idempotency_key === $key) {
                    return $attendance;
                }

                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah melakukan presensi pulang hari ini pada '
                        .$attendance->checkOutLocal()?->format('H:i').'.',
                ]);
            }

            $attendance->fill([
                'check_out_at' => now(),
                'check_out_lat' => $data['lat'],
                'check_out_lng' => $data['lng'],
                'check_out_accuracy_m' => $data['accuracy_m'],
                'check_out_distance_m' => round($distance, 2),
                'check_out_photo_path' => $data['photo_path'],
                'check_out_ip' => $data['ip'] ?? null,
                'check_out_user_agent' => isset($data['user_agent']) ? substr((string) $data['user_agent'], 0, 255) : null,
                'check_out_idempotency_key' => $key,
            ]);

            $this->recalculate($attendance, $shift);
            $attendance->save();

            return $attendance;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitCorrection(Employee $employee, array $data, User $actor): AttendanceCorrection
    {
        $date = (string) $data['work_date'];

        $supervisorId = $employee->direct_supervisor_id;
        $supervisorUser = $supervisorId
            ? User::query()->where('employee_id', $supervisorId)->where('is_active', true)->first()
            : null;

        $status = $supervisorUser ? 'menunggu_atasan' : 'menunggu_hrd';

        if (isset($data['requested_check_in_at'])) {
            $data['requested_check_in_at'] = $this->localToUtc($date, $data['requested_check_in_at']);
        }

        if (isset($data['requested_check_out_at'])) {
            $data['requested_check_out_at'] = $this->localToUtc($date, $data['requested_check_out_at']);
        }

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $date)
            ->latest('id')
            ->first();

        $correction = AttendanceCorrection::create([
            'employee_id' => $employee->id,
            'attendance_id' => $data['attendance_id'] ?? $attendance?->id,
            'work_date' => $date,
            'correction_type' => $data['correction_type'],
            'requested_check_in_at' => $data['requested_check_in_at'] ?? null,
            'requested_check_out_at' => $data['requested_check_out_at'] ?? null,
            'reason' => $data['reason'],
            'evidence_path' => $data['evidence_path'] ?? null,
            'status' => $status,
            'supervisor_id' => $supervisorId,
        ]);

        if ($supervisorUser) {
            $this->notifications->send(
                $supervisorUser,
                'attendance_correction',
                'Koreksi absensi menunggu',
                $employee->full_name.' mengajukan koreksi absensi tanggal '.$date.'.',
                ['correction_id' => $correction->id, 'dedupe_key' => 'correction-'.$correction->id.'-supervisor']
            );
        } else {
            $this->notifyCorrectionHrd($correction);
        }

        return $correction;
    }

    public function approveCorrectionBySupervisor(AttendanceCorrection $correction, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($correction, $actor, $note): void {
            $correction = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);

            if ($correction->status !== 'menunggu_atasan') {
                throw ValidationException::withMessages([
                    'correction' => 'Koreksi tidak berada pada tahap persetujuan atasan.',
                ]);
            }

            if ($actor->employee_id !== $correction->supervisor_id) {
                throw ValidationException::withMessages([
                    'correction' => 'Hanya atasan langsung pegawai yang dapat menyetujui tahap ini.',
                ]);
            }

            $correction->update([
                'status' => 'menunggu_hrd',
                'supervisor_decision_at' => now(),
                'supervisor_note' => $note,
            ]);

            $this->notifyCorrectionHrd($correction);
        });
    }

    public function approveCorrectionByHrd(AttendanceCorrection $correction, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($correction, $actor, $note): void {
            $correction = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);

            if ($correction->status !== 'menunggu_hrd') {
                throw ValidationException::withMessages([
                    'correction' => 'Koreksi tidak berada pada tahap verifikasi HRD.',
                ]);
            }

            $this->applyCorrection($correction);

            $correction->update([
                'status' => 'disetujui',
                'hrd_id' => $actor->id,
                'hrd_decision_at' => now(),
                'hrd_note' => $note,
                'applied_at' => now(),
            ]);

            $this->notifyEmployee($correction->employee, 'Koreksi absensi disetujui', 'Koreksi absensi tanggal '
                .$correction->work_date->toDateString().' telah disetujui dan diterapkan.');
        });
    }

    public function rejectCorrection(AttendanceCorrection $correction, User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        DB::transaction(function () use ($correction, $actor, $reason): void {
            $correction = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);

            if (! in_array($correction->status, ['menunggu_atasan', 'menunggu_hrd'], true)) {
                throw ValidationException::withMessages([
                    'correction' => 'Koreksi sudah selesai diproses.',
                ]);
            }

            if ($correction->status === 'menunggu_atasan') {
                if ($actor->employee_id !== $correction->supervisor_id) {
                    throw ValidationException::withMessages([
                        'correction' => 'Hanya atasan langsung yang dapat menolak pada tahap ini.',
                    ]);
                }

                $correction->update([
                    'status' => 'ditolak',
                    'supervisor_decision_at' => now(),
                    'supervisor_note' => $reason,
                ]);
            } else {
                if (! $actor->hasPermission('attendance.correction.approve.hrd')) {
                    throw ValidationException::withMessages([
                        'correction' => 'Anda tidak berwenang menolak pada tahap HRD.',
                    ]);
                }

                $correction->update([
                    'status' => 'ditolak',
                    'hrd_id' => $actor->id,
                    'hrd_decision_at' => now(),
                    'hrd_note' => $reason,
                ]);
            }

            $this->notifyEmployee(
                $correction->employee,
                'Koreksi absensi ditolak',
                'Koreksi absensi tanggal '.$correction->work_date->toDateString().' ditolak: '.$reason
            );
        });
    }

    public function cancelCorrection(AttendanceCorrection $correction, User $actor): void
    {
        if (! in_array($correction->status, ['menunggu_atasan', 'menunggu_hrd'], true)) {
            throw ValidationException::withMessages(['correction' => 'Koreksi sudah selesai diproses.']);
        }

        if ($actor->employee_id !== $correction->employee_id && ! $actor->hasRole('administrator')) {
            throw ValidationException::withMessages(['correction' => 'Hanya pengaju yang dapat membatalkan koreksi.']);
        }

        $correction->update(['status' => 'dibatalkan']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitException(Employee $employee, array $data, User $actor): AttendanceException
    {
        $date = (string) $data['work_date'];

        if (isset($data['check_in_at'])) {
            $data['check_in_at'] = $this->localToUtc($date, $data['check_in_at']);
        }

        if (isset($data['check_out_at'])) {
            $data['check_out_at'] = $this->localToUtc($date, $data['check_out_at']);
        }

        $exception = AttendanceException::create([
            'employee_id' => $employee->id,
            'work_date' => $date,
            'type' => $data['type'],
            'check_in_at' => $data['check_in_at'] ?? null,
            'check_out_at' => $data['check_out_at'] ?? null,
            'reason' => $data['reason'],
            'evidence_path' => $data['evidence_path'] ?? null,
            'status' => 'menunggu',
        ]);

        $this->notifications->sendToPermission(
            'attendance.exception.manage',
            'attendance_exception',
            'Dispensasi absensi menunggu',
            $employee->full_name.' mengajukan dispensasi absensi tanggal '.$date.'.',
            ['exception_id' => $exception->id, 'dedupe_key' => 'exception-'.$exception->id]
        );

        return $exception;
    }

    public function approveException(AttendanceException $exception, User $actor): void
    {
        DB::transaction(function () use ($exception, $actor): void {
            $exception = AttendanceException::query()->lockForUpdate()->findOrFail($exception->id);

            if ($exception->status !== 'menunggu') {
                throw ValidationException::withMessages(['exception' => 'Dispensasi sudah diproses.']);
            }

            $employee = $exception->employee;
            $resolved = $this->resolveSchedule($employee, $exception->work_date->toDateString());

            if (! $resolved['shift'] instanceof WorkShift) {
                throw ValidationException::withMessages([
                    'exception' => 'Shift kerja pada tanggal tersebut tidak ditemukan.',
                ]);
            }

            $shift = $resolved['shift'];

            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $exception->work_date)
                ->where('shift_id', $shift->id)
                ->lockForUpdate()
                ->first();

            if ($attendance?->is_locked) {
                throw ValidationException::withMessages([
                    'exception' => 'Data absensi tanggal tersebut sudah dikunci payroll.',
                ]);
            }

            if (! $attendance) {
                $attendance = new Attendance([
                    'employee_id' => $employee->id,
                    'work_date' => $exception->work_date,
                    'shift_id' => $shift->id,
                    'schedule_assignment_id' => $resolved['assignment']?->id,
                    'work_location_id' => $resolved['location']?->id,
                ]);
            }

            $attendance->fill([
                'check_in_at' => $exception->check_in_at ?? $attendance->check_in_at,
                'check_out_at' => $exception->check_out_at ?? $attendance->check_out_at,
                'status' => 'dispensasi',
                'source' => 'system',
                'notes' => 'Dispensasi #'.$exception->id.' ('.$exception->type.')',
            ]);

            $this->recalculate($attendance, $shift, keepStatus: true);
            $attendance->save();

            $exception->update([
                'status' => 'disetujui',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->notifyEmployee(
                $employee,
                'Dispensasi absensi disetujui',
                'Dispensasi absensi tanggal '.$exception->work_date->toDateString().' telah disetujui.'
            );
        });
    }

    public function rejectException(AttendanceException $exception, User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        if ($exception->status !== 'menunggu') {
            throw ValidationException::withMessages(['exception' => 'Dispensasi sudah diproses.']);
        }

        $exception->update([
            'status' => 'ditolak',
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'reason' => $exception->reason."\n\nPenolakan: ".$reason,
        ]);

        $this->notifyEmployee(
            $exception->employee,
            'Dispensasi absensi ditolak',
            'Dispensasi absensi tanggal '.$exception->work_date->toDateString().' ditolak: '.$reason
        );
    }

    public function storePhoto(UploadedFile $file, string $folder): string
    {
        $path = $file->store($folder.'/'.now('Asia/Jakarta')->format('Y/m'), 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'photo' => 'Foto gagal disimpan. Silakan ulangi.',
            ]);
        }

        return $path;
    }

    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @return array<int, string>
     */
    public function recentDates(int $days = 7): array
    {
        $dates = [];

        for ($i = 0; $i < $days; $i++) {
            $dates[] = now('Asia/Jakarta')->subDays($i)->toDateString();
        }

        return $dates;
    }

    protected function applyCorrection(AttendanceCorrection $correction): void
    {
        $employee = $correction->employee;
        $resolved = $this->resolveSchedule($employee, $correction->work_date->toDateString());

        if (! $resolved['shift'] instanceof WorkShift) {
            throw ValidationException::withMessages([
                'correction' => 'Shift kerja pada tanggal tersebut tidak ditemukan.',
            ]);
        }

        $shift = $resolved['shift'];

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $correction->work_date)
            ->where('shift_id', $shift->id)
            ->lockForUpdate()
            ->first();

        if ($attendance?->is_locked) {
            throw ValidationException::withMessages([
                'correction' => 'Data absensi tanggal tersebut sudah dikunci payroll.',
            ]);
        }

        if (! $attendance) {
            $attendance = new Attendance([
                'employee_id' => $employee->id,
                'work_date' => $correction->work_date,
                'shift_id' => $shift->id,
                'schedule_assignment_id' => $resolved['assignment']?->id,
            ]);
        }

        $type = $correction->correction_type;

        if ($type === 'tidak_hadir') {
            $attendance->fill([
                'check_in_at' => null,
                'check_out_at' => null,
                'check_in_lat' => null,
                'check_in_lng' => null,
                'check_in_accuracy_m' => null,
                'check_in_distance_m' => null,
                'check_in_photo_path' => null,
                'check_out_lat' => null,
                'check_out_lng' => null,
                'check_out_accuracy_m' => null,
                'check_out_distance_m' => null,
                'check_out_photo_path' => null,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'work_minutes' => 0,
                'status' => 'tidak_hadir',
            ]);
        } else {
            if (in_array($type, ['masuk', 'keduanya'], true)) {
                if (! $correction->requested_check_in_at) {
                    throw ValidationException::withMessages([
                        'correction' => 'Jam masuk yang diminta belum diisi.',
                    ]);
                }

                $attendance->check_in_at = $correction->requested_check_in_at;
            }

            if (in_array($type, ['pulang', 'keduanya'], true)) {
                if (! $correction->requested_check_out_at) {
                    throw ValidationException::withMessages([
                        'correction' => 'Jam pulang yang diminta belum diisi.',
                    ]);
                }

                $attendance->check_out_at = $correction->requested_check_out_at;
            }

            if (! $attendance->check_in_at) {
                throw ValidationException::withMessages([
                    'correction' => 'Koreksi pulang membutuhkan jam masuk yang tercatat.',
                ]);
            }

            $this->recalculate($attendance, $shift);
        }

        $attendance->fill([
            'source' => 'correction',
            'is_corrected' => true,
            'notes' => 'Koreksi #'.$correction->id.' disetujui',
        ]);

        $attendance->save();
    }

    protected function recalculate(Attendance $attendance, WorkShift $shift, bool $keepStatus = false): void
    {
        if ($attendance->check_in_at) {
            $attendance->late_minutes = $this->lateMinutes($attendance, $shift);
        }

        if ($attendance->check_out_at) {
            $attendance->early_leave_minutes = $this->earlyLeaveMinutes($attendance, $shift);
        }

        if ($attendance->check_in_at && $attendance->check_out_at) {
            $attendance->work_minutes = max(
                0,
                (int) $attendance->check_in_at->diffInMinutes($attendance->check_out_at, false) - $shift->break_minutes
            );
        }

        if ($keepStatus) {
            return;
        }

        $attendance->status = match (true) {
            $attendance->late_minutes > 0 => 'terlambat',
            $attendance->early_leave_minutes > 0 => 'pulang_awal',
            default => 'hadir',
        };
    }

    protected function lateMinutes(Attendance $attendance, WorkShift $shift): int
    {
        if (! $attendance->check_in_at) {
            return 0;
        }

        $start = Carbon::parse($attendance->work_date->toDateString().' '.$shift->start_time, 'Asia/Jakarta');
        $actual = $attendance->check_in_at->copy()->timezone('Asia/Jakarta');

        return max(0, (int) $start->diffInMinutes($actual, false) - $shift->late_tolerance_minutes);
    }

    protected function earlyLeaveMinutes(Attendance $attendance, WorkShift $shift): int
    {
        if (! $attendance->check_out_at) {
            return 0;
        }

        $end = Carbon::parse($attendance->work_date->toDateString().' '.$shift->end_time, 'Asia/Jakarta');

        if ($shift->is_cross_midnight) {
            $end->addDay();
        }

        $actual = $attendance->check_out_at->copy()->timezone('Asia/Jakarta');

        return max(0, (int) $actual->diffInMinutes($end, false) - $shift->early_leave_tolerance_minutes);
    }

    protected function activeAttendanceForCheckOut(Employee $employee): ?Attendance
    {
        $today = now('Asia/Jakarta')->toDateString();

        $attendance = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest('id')
            ->first();

        if ($attendance) {
            return $attendance;
        }

        // Shift lintas tengah malam: presensi pulang dilakukan pada tanggal kalender berikutnya.
        $attendance = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', now('Asia/Jakarta')->subDay()->toDateString())
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest('id')
            ->first();

        if (! $attendance?->shift?->is_cross_midnight) {
            return null;
        }

        $end = Carbon::parse(
            $attendance->work_date->toDateString().' '.$attendance->shift->end_time,
            'Asia/Jakarta'
        )->addDay();

        return now('Asia/Jakarta')->lte($end->copy()->addHours(4)) ? $attendance : null;
    }

    /**
     * @return array<int, string>
     */
    protected function idempotencyKey(?string $key): string
    {
        $key = trim((string) $key);

        return $key !== '' ? $key : Str::uuid()->toString();
    }

    protected function localToUtc(string $date, string $time): Carbon
    {
        if (str_contains($time, 'T') || str_contains($time, ' ')) {
            return Carbon::parse($time, 'Asia/Jakarta')->utc();
        }

        $time = substr($time, 0, 5);

        return Carbon::parse($date.' '.$time, 'Asia/Jakarta')->utc();
    }

    protected function assertLocation(?WorkLocation $location): void
    {
        if (! $location) {
            throw ValidationException::withMessages([
                'attendance' => 'Lokasi kerja pegawai belum diatur. Hubungi HRD.',
            ]);
        }
    }

    protected function notifyCorrectionHrd(AttendanceCorrection $correction): void
    {
        $this->notifications->sendToPermission(
            'attendance.correction.approve.hrd',
            'attendance_correction',
            'Koreksi absensi menunggu verifikasi HRD',
            $correction->employee->full_name.' — koreksi absensi tanggal '
                .$correction->work_date->toDateString().'.',
            [
                'correction_id' => $correction->id,
                'dedupe_key' => 'correction-'.$correction->id.'-hrd',
            ]
        );
    }

    protected function notifyEmployee(Employee $employee, string $title, string $message): void
    {
        $user = $employee->user;

        if ($user) {
            $this->notifications->send($user, 'attendance', $title, $message, [
                'employee_id' => $employee->id,
            ]);
        }
    }
}
