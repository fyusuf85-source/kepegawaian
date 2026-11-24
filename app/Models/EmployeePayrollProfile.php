<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollProfile extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'education_id', 'transport_distance_km',
        'position_allowance_class', 'bpjs_kesehatan_no', 'bpjs_tk_no',
        'npwp', 'tax_status', 'dependents_count', 'salary_basis_override',
        'override_reason', 'effective_from', 'updated_by', 'last_salary_amount',
        'last_salary_effective_from', 'spouse_status', 'child_count',
        'bpjs_tk_program', 'bpjs_health_participates', 'tax_borne_by_employer',
        'spouse_is_employee', 'special_allowance_amount', 'transport_days', 'snack_days',
        'snack_payment_method', 'annual_increase_enabled', 'education_increase_enabled',
    ];

    protected function casts(): array
    {
        return [
            'transport_distance_km' => 'decimal:2',
            'salary_basis_override' => 'decimal:2',
            'dependents_count' => 'integer',
            'child_count' => 'integer',
            'last_salary_amount' => 'decimal:2',
            'last_salary_effective_from' => 'date',
            'spouse_status' => 'boolean',
            'bpjs_health_participates' => 'boolean',
            'tax_borne_by_employer' => 'boolean',
            'spouse_is_employee' => 'boolean',
            'special_allowance_amount' => 'decimal:2',
            'transport_days' => 'integer',
            'snack_days' => 'integer',
            'annual_increase_enabled' => 'boolean',
            'education_increase_enabled' => 'boolean',
            'effective_from' => 'date',
            'bpjs_kesehatan_no' => 'encrypted',
            'bpjs_tk_no' => 'encrypted',
            'npwp' => 'encrypted',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
