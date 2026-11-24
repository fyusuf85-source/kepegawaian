<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id', 'work_date', 'shift_id', 'schedule_assignment_id', 'work_location_id',
        'check_in_at', 'check_in_lat', 'check_in_lng', 'check_in_accuracy_m',
        'check_in_distance_m', 'check_in_photo_path', 'check_in_ip',
        'check_in_user_agent', 'check_in_idempotency_key',
        'check_out_at', 'check_out_lat', 'check_out_lng', 'check_out_accuracy_m',
        'check_out_distance_m', 'check_out_photo_path', 'check_out_ip',
        'check_out_user_agent', 'check_out_idempotency_key',
        'late_minutes', 'early_leave_minutes', 'work_minutes',
        'status', 'source', 'is_corrected', 'is_locked', 'payroll_period_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_lat' => 'decimal:7',
            'check_in_lng' => 'decimal:7',
            'check_out_lat' => 'decimal:7',
            'check_out_lng' => 'decimal:7',
            'check_in_accuracy_m' => 'decimal:2',
            'check_out_accuracy_m' => 'decimal:2',
            'check_in_distance_m' => 'decimal:2',
            'check_out_distance_m' => 'decimal:2',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'work_minutes' => 'integer',
            'is_corrected' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'shift_id');
    }

    public function scheduleAssignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function corrections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function checkInLocal(): ?Carbon
    {
        return $this->check_in_at?->copy()->timezone('Asia/Jakarta');
    }

    public function checkOutLocal(): ?Carbon
    {
        return $this->check_out_at?->copy()->timezone('Asia/Jakarta');
    }

    public static function statusLabels(): array
    {
        return [
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'pulang_awal' => 'Pulang awal',
            'tidak_hadir' => 'Tidak hadir',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'dispensasi' => 'Dispensasi',
            'libur' => 'Libur',
        ];
    }
}
