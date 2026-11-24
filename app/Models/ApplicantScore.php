<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantScore extends Model
{
    protected $fillable = ['applicant_id', 'criterion', 'weight', 'score', 'notes', 'scored_by'];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'score' => 'decimal:2',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function scorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }
}
