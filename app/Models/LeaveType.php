<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'requires_document', 'document_required_after_days',
        'affects_balance', 'is_paid', 'max_days_per_year', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_document' => 'boolean',
            'document_required_after_days' => 'integer',
            'affects_balance' => 'boolean',
            'is_paid' => 'boolean',
            'max_days_per_year' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
