<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiIndicator extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }
}
