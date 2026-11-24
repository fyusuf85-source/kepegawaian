<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'vacancy_id', 'code', 'full_name', 'nik', 'birth_place', 'birth_date',
        'gender', 'phone', 'email', 'address', 'education_id', 'experience_years',
        'expected_salary', 'source', 'status', 'final_score', 'notes',
        'converted_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'birth_date' => 'date',
            'experience_years' => 'integer',
            'expected_salary' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'vacancy_id');
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderBy('scheduled_at');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicantScore::class);
    }

    public function convertedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'converted_employee_id');
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'diterima' && $this->converted_employee_id === null;
    }

    /**
     * Hitung skor akhir berbobot (weight dalam persen, total wajib 100).
     */
    public function calculateFinalScore(): float
    {
        return round((float) $this->scores->sum(fn (ApplicantScore $score) => (float) $score->score * (float) $score->weight / 100), 2);
    }
}
