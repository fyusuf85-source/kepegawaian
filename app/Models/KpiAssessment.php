<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiAssessment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['final_score' => 'decimal:2'];
    }

    public function period(): BelongsTo { return $this->belongsTo(KpiPeriod::class, 'kpi_period_id'); }
    public function template(): BelongsTo { return $this->belongsTo(KpiTemplate::class, 'kpi_template_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function assessor(): BelongsTo { return $this->belongsTo(Employee::class, 'assessor_id'); }
    public function items(): HasMany { return $this->hasMany(KpiAssessmentItem::class); }
}
