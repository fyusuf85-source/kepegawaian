<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    public const CREATED_AT = null;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'year',
        'entitlement_days', 'used_days', 'adjustment_days',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitlement_days' => 'decimal:1',
            'used_days' => 'decimal:1',
            'adjustment_days' => 'decimal:1',
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

    public function remainingDays(): float
    {
        return (float) $this->entitlement_days
            + (float) $this->adjustment_days
            - (float) $this->used_days;
    }
}
