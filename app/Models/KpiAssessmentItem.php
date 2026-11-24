<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiAssessmentItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'target' => 'decimal:2',
            'actual' => 'decimal:2',
            'score' => 'decimal:2',
            'weighted_score' => 'decimal:2',
        ];
    }

    public function assessment(): BelongsTo { return $this->belongsTo(KpiAssessment::class, 'kpi_assessment_id'); }
    public function indicator(): BelongsTo { return $this->belongsTo(KpiIndicator::class, 'kpi_indicator_id'); }
}
