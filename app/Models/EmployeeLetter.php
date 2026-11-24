<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLetter extends Model implements Approvable
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'letter_type', 'number', 'letter_date', 'subject', 'body',
        'document_path', 'status', 'coaching_record_id', 'created_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return ['letter_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalFlowCode(): string
    {
        return 'letter';
    }

    public function approvalLabel(): string
    {
        return 'Surat '.$this->number.' — '.$this->subject;
    }

    public function approvalOrgUnitId(): ?int
    {
        return $this->employee?->org_unit_id;
    }

    public function markApprovalPending(): void
    {
        $this->update(['status' => 'menunggu_persetujuan']);
    }

    public function markApprovalApproved(User $actor): void
    {
        $this->update(['status' => 'disetujui', 'approved_by' => $actor->id]);
    }

    public function markApprovalRejected(User $actor, string $reason): void
    {
        $this->update(['status' => 'dibatalkan', 'approved_by' => $actor->id]);
    }
}
