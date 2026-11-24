<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_rate' => 'decimal:4', 'basis_amount' => 'decimal:2', 'amount' => 'decimal:2'];
    }

    public function runEmployee(): BelongsTo { return $this->belongsTo(PayrollRunEmployee::class, 'payroll_run_employee_id'); }
    public function component(): BelongsTo { return $this->belongsTo(PayrollComponent::class); }
}
