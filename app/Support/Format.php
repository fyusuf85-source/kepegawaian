<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Format
{
    public static function date(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        return Carbon::parse($value)->timezone('Asia/Jakarta')->format('d-m-Y');
    }

    public static function dateTime(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        return Carbon::parse($value)->timezone('Asia/Jakarta')->format('d-m-Y H:i');
    }

    public static function money(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    public static function time(mixed $value): string
    {
        return blank($value) ? '—' : substr((string) $value, 0, 5);
    }

    /**
     * Bersihkan nilai untuk dipakai sebagai nama berkas unduhan.
     */
    public static function filename(string $value): string
    {
        return str_replace(['/', '\\'], '-', $value);
    }

    public static function dayName(int $day): string
    {
        return ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][$day] ?? '—';
    }
}
