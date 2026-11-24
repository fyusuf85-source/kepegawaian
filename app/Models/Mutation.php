<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mutation extends Model implements Approvable
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'mutation_type', 'from_org_unit_id', 'to_org_unit_id',
        'from_position_id', 'to_position_id', 'from_supervisor_id', 'to_supervisor_id',
        'effective_date', 'reason', 'decree_id', 'status', 'requested_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromOrgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'from_org_unit_id');
    }

    public function toOrgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'to_org_unit_id');
    }

    public function fromPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }

    public function toPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }

    public function fromSupervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_supervisor_id');
    }

    public function toSupervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_supervisor_id');
    }

    public function decree(): BelongsTo
    {
        return $this->belongsTo(Decree::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalFlowCode(): string
    {
        return 'mutation';
    }

    public function approvalLabel(): string
    {
        return 'Mutasi '.$this->employee?->full_name.' per '.$this->effective_date?->format('d-m-Y');
    }

    public function approvalOrgUnitId(): ?int
    {
        return $this->employee?->org_unit_id;
    }

    public function markApprovalPending(): void
    {
        $this->update(['status' => 'menunggu']);
    }

    public function markApprovalApproved(User $actor): void
    {
        $this->update(['status' => 'disetujui', 'approved_by' => $actor->id]);
    }

    public function markApprovalRejected(User $actor, string $reason): void
    {
        $this->update(['status' => 'ditolak', 'approved_by' => $actor->id]);
    }
}
