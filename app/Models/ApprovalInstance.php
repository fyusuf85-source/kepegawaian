<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalInstance extends Model
{
    protected $fillable = [
        'approval_flow_id', 'subject_type', 'subject_id', 'status', 'current_step',
        'requested_by', 'requested_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class)->orderBy('sequence');
    }

    public function currentStep(): ?ApprovalFlowStep
    {
        return $this->flow->steps->firstWhere('sequence', $this->current_step);
    }

    public function isPending(): bool
    {
        return $this->status === 'menunggu';
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'menunggu');
    }
}
