<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestDay extends Model
{
    protected $fillable = ['leave_request_id', 'date', 'is_half_day'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_half_day' => 'boolean',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
