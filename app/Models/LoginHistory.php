<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username_attempt',
        'success',
        'failure_reason',
        'ip_address',
        'user_agent',
        'login_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'login_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
