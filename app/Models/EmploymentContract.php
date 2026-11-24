<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentContract extends Model implements Approvable
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'contract_number', 'contract_type', 'start_date', 'end_date',
        'probation_end_date', 'status', 'document_path', 'signed_date',
        'previous_contract_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'signed_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function previousContract(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_contract_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'previous_contract_id');
    }

    public function approvalFlowCode(): string
    {
        return 'contract';
    }

    public function approvalLabel(): string
    {
        return 'Kontrak '.$this->contract_number.' — '.$this->employee?->full_name;
    }

    public function approvalOrgUnitId(): ?int
    {
        return $this->employee?->org_unit_id;
    }

    public function markApprovalPending(): void
    {
        // Sesuai DATABASE_DESIGN §7.1, kontrak tetap berstatus draf sampai disetujui.
    }

    public function markApprovalApproved(User $actor): void
    {
        $this->update(['status' => 'aktif']);

        $previous = $this->previousContract;
        if ($previous && in_array($previous->status, ['aktif', 'akan_berakhir'], true)) {
            $previous->update(['status' => 'diperpanjang']);
        }
    }

    public function markApprovalRejected(User $actor, string $reason): void
    {
        $this->update(['status' => 'dibatalkan', 'notes' => trim(($this->notes ? $this->notes."\n" : '').'Ditolak: '.$reason)]);
    }
}
