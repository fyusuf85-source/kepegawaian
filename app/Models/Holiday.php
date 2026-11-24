<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'name', 'type', 'is_paid'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_paid' => 'boolean',
        ];
    }
}
