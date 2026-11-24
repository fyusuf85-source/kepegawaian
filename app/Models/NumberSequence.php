<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $fillable = [
        'code', 'prefix', 'pattern', 'next_number', 'padding', 'reset_period', 'last_reset_at',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'padding' => 'integer',
            'last_reset_at' => 'date',
        ];
    }
}
