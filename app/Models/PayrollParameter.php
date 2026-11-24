<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollParameter extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }
}
