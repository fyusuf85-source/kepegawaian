<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NumberSequenceService
{
    /**
     * Ambil nomor berikutnya secara atomik (SELECT ... FOR UPDATE).
     */
    public function next(string $code, ?CarbonInterface $at = null): string
    {
        $at = $at ? Carbon::instance($at) : now('Asia/Jakarta');

        return DB::transaction(function () use ($code, $at): string {
            $sequence = DB::table('number_sequences')
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException("Nomor urut '{$code}' belum dikonfigurasi.");
            }

            $next = (int) $sequence->next_number;
            $reset = false;
            $lastReset = $sequence->last_reset_at ? Carbon::parse($sequence->last_reset_at) : null;

            if ($sequence->reset_period === 'yearly'
                && (! $lastReset || $lastReset->year !== $at->year)) {
                $next = 1;
                $reset = true;
            }

            if ($sequence->reset_period === 'monthly'
                && (! $lastReset || $lastReset->format('Y-m') !== $at->format('Y-m'))) {
                $next = 1;
                $reset = true;
            }

            $number = $this->format(
                $sequence->pattern,
                $sequence->prefix,
                $next,
                $sequence->padding,
                $at
            );

            DB::table('number_sequences')->where('id', $sequence->id)->update([
                'next_number' => $next + 1,
                'last_reset_at' => $reset ? $at->toDateString() : $sequence->last_reset_at,
                'updated_at' => now(),
            ]);

            return $number;
        });
    }

    public function format(
        string $pattern,
        ?string $prefix,
        int $number,
        int $padding,
        CarbonInterface $at
    ): string {
        $replaced = str_replace(
            ['{prefix}', '{year}', '{month}', '{day}'],
            [$prefix ?? '', $at->format('Y'), $at->format('m'), $at->format('d')],
            $pattern
        );

        return preg_replace_callback(
            '/\{seq(?::(\d+))?\}/',
            fn (array $match) => str_pad(
                (string) $number,
                isset($match[1]) && $match[1] !== '' ? (int) $match[1] : $padding,
                '0',
                STR_PAD_LEFT
            ),
            $replaced
        );
    }
}
