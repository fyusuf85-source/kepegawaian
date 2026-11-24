<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobVacancy extends Model
{
    use Auditable;

    protected $fillable = [
        'code', 'title', 'org_unit_id', 'position_id', 'employee_category',
        'quota', 'description', 'requirements', 'open_date', 'close_date',
        'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'open_date' => 'date',
            'close_date' => 'date',
            'quota' => 'integer',
        ];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class, 'vacancy_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        $today = now('Asia/Jakarta')->toDateString();

        return $this->status === 'dibuka'
            && $this->open_date?->toDateString() <= $today
            && $this->close_date?->toDateString() >= $today;
    }
}
