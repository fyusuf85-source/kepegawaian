<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    protected $table = 'educations';

    protected $fillable = ['code', 'name', 'level_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'level_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
