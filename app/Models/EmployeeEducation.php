<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    protected $table = 'employee_educations';

    protected $fillable = [
        'employee_id', 'education_id', 'institution', 'major',
        'graduation_year', 'certificate_path', 'is_highest',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'is_highest' => 'boolean',
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
}
