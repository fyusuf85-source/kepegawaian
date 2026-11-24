<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'start_time', 'end_time', 'is_cross_midnight',
        'break_minutes', 'late_tolerance_minutes', 'early_leave_tolerance_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_cross_midnight' => 'boolean',
            'break_minutes' => 'integer',
            'late_tolerance_minutes' => 'integer',
            'early_leave_tolerance_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scheduleDays(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class, 'shift_id');
    }
}
