<?php

namespace App\Models;

use App\Contracts\Approvable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model implements Approvable
{
    use HasFactory;

    protected $fillable = ['code', 'start_date', 'end_date', 'payment_date', 'status', 'locked_at', 'locked_by', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'payment_date' => 'date', 'locked_at' => 'datetime'];
    }

    public function summaries(): HasMany { return $this->hasMany(AttendancePeriodSummary::class); }
    public function runs(): HasMany { return $this->hasMany(PayrollRun::class); }
    public function lockedBy(): BelongsTo { return $this->belongsTo(User::class, 'locked_by'); }
    public function isLocked(): bool { return $this->status === 'dikunci'; }

    public function approvalFlowCode(): string { return 'payroll'; }
    public function approvalLabel(): string { return 'Periode payroll '.$this->code; }
    public function approvalOrgUnitId(): ?int { return null; }
    public function markApprovalPending(): void { $this->update(['status' => 'verifikasi_hrd']); }
    public function markApprovalApproved(User $actor): void { $this->update(['status' => 'disetujui']); }
    public function markApprovalRejected(User $actor, string $reason): void { $this->update(['status' => 'dihitung', 'notes' => trim(($this->notes ? $this->notes."\n\n" : '').'Ditolak: '.$reason)]); }
}
