<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id', 'attendance_id', 'work_date', 'correction_type',
        'requested_check_in_at', 'requested_check_out_at', 'reason', 'evidence_path',
        'status', 'supervisor_id', 'supervisor_decision_at', 'supervisor_note',
        'hrd_id', 'hrd_decision_at', 'hrd_note', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'requested_check_in_at' => 'datetime',
            'requested_check_out_at' => 'datetime',
            'supervisor_decision_at' => 'datetime',
            'hrd_decision_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function hrd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hrd_id');
    }

    public static function typeLabels(): array
    {
        return [
            'masuk' => 'Jam masuk',
            'pulang' => 'Jam pulang',
            'keduanya' => 'Masuk & pulang',
            'tidak_hadir' => 'Tidak hadir',
            'status' => 'Status kehadiran',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'menunggu_atasan' => 'Menunggu atasan',
            'menunggu_hrd' => 'Menunggu HRD',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
        ];
    }
}
