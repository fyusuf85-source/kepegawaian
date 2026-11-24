<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reimbursement extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'supervisor_id', 'title', 'amount', 'expense_date',
        'description', 'attachment_path', 'status', 'rejection_reason',
        'approved_by_supervisor_id', 'approved_by_supervisor_at',
        'approved_by_hrd_id', 'approved_by_hrd_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'approved_by_supervisor_at' => 'datetime',
            'approved_by_hrd_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function supervisorApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_supervisor_id');
    }

    public function hrdApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_hrd_id');
    }
}
