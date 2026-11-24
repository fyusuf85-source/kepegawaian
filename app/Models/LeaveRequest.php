<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days',
        'reason', 'document_path', 'status', 'current_step', 'submitted_at',
        'decided_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'integer',
            'current_step' => 'integer',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(LeaveRequestDay::class)->orderBy('date');
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['menunggu_atasan', 'menunggu_hrd'], true);
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
