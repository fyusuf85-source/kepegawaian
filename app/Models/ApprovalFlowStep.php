<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalFlowStep extends Model
{
    protected $fillable = ['approval_flow_id', 'sequence', 'role_id', 'scope', 'is_optional'];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_optional' => 'boolean',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
