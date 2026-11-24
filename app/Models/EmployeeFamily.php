<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamily extends Model
{
    protected $fillable = [
        'employee_id', 'relation', 'name', 'nik', 'gender', 'birth_date',
        'education', 'occupation', 'is_dependent', 'is_working_at_pis',
        'marriage_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'birth_date' => 'date',
            'marriage_date' => 'date',
            'is_dependent' => 'boolean',
            'is_working_at_pis' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
