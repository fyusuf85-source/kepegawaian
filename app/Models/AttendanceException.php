<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceException extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id', 'work_date', 'type', 'check_in_at', 'check_out_at',
        'reason', 'evidence_path', 'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function typeLabels(): array
    {
        return [
            'gps_tidak_tersedia' => 'GPS tidak tersedia',
            'kamera_tidak_tersedia' => 'Kamera tidak tersedia',
            'penugasan_luar' => 'Penugasan luar',
            'darurat' => 'Darurat',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'menunggu' => 'Menunggu',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
    }
}
