<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Decree extends Model implements Approvable
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'decree_type', 'number', 'decree_date', 'effective_date',
        'title', 'description', 'document_path', 'status', 'approved_by',
        'approved_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'decree_date' => 'date',
            'effective_date' => 'date',
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

    public function mutations(): HasMany
    {
        return $this->hasMany(Mutation::class);
    }

    public function approvalFlowCode(): string
    {
        return 'decree';
    }

    public function approvalLabel(): string
    {
        return 'SK '.$this->number.' — '.$this->employee?->full_name;
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
        $this->update([
            'status' => 'disetujui',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);
    }

    public function markApprovalRejected(User $actor, string $reason): void
    {
        $this->update(['status' => 'ditolak']);
    }
}
