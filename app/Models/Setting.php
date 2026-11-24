<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group', 'key', 'value', 'value_type', 'effective_from', 'description', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['effective_from' => 'date'];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function typedValue(): mixed
    {
        return match ($this->value_type) {
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'date' => json_decode((string) $this->value, true) ?? $this->value,
            default => $this->value,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('setting.'.$key, fn () => static::query()->where('key', $key)->first()?->typedValue()) ?? $default;
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget('setting.'.$setting->key));
        static::deleted(fn (Setting $setting) => Cache::forget('setting.'.$setting->key));
    }
}
