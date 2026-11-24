<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollParameter;
use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BpjsCalculator
{
    public function calculate(Employee $employee, object $profile, PayrollPeriod $period, Collection $parameters, Collection $components, float $baseIncome, float $fullAttendance): array
    {
        $status = strtolower((string) ($employee->employment_status ?? $employee->employee_category ?? ''));
        if (str_contains($status, 'percobaan') || in_array(strtolower((string) $profile->bpjs_tk_program), ['tidak', 'tidak ikut', 'none'], true)) {
            return [];
        }

        $details = [];
        $programType = strtolower((string) $profile->bpjs_tk_program);
        $isTwoProgram = str_contains($programType, '2') || str_contains($status, 'khidmah') && ! str_contains($status, 'khusus');
        $isDaily = str_contains($status, 'harian');
        $base = $isDaily ? (float) $parameters->get('daily_rate_harian')?->value * 30 : $baseIncome + $fullAttendance;
        $programs = $isTwoProgram ? ['bpjs_tk_jkk', 'bpjs_tk_jkm'] : ['bpjs_tk_jkk', 'bpjs_tk_jkm', 'bpjs_tk_jht'];
        $umr = (float) ($parameters->get('umr_aceh_besar')?->value ?? 3932552);
        if ($isTwoProgram && $base < $umr) $base = $umr;

        foreach ($programs as $programCode) {
            foreach (['employer' => 'iuran_pemberi', 'employee' => 'potongan'] as $participant => $type) {
                $setting = DB::table('bpjs_settings')->where('program', $programCode)->where('participant_type', $participant)->where('effective_from', '<=', $period->end_date)->orderByDesc('effective_from')->first();
                if (! $setting) continue;
                $amount = ceil($base * (float) $setting->rate / 100);
                if ($amount <= 0) continue;
                $componentCode = $this->componentCode($programCode, $participant);
                $component = $components->get($componentCode);
                if (! $component) continue;
                $details[] = $this->line($component, $type, $amount, $base, (float) $setting->rate, strtoupper($programCode).' '.number_format((float) $setting->rate, 2).'%');
            }
        }

        if ($profile->bpjs_health_participates) {
            $healthBase = (float) ($parameters->get('bpjs_kes_base')?->value ?? 4200000);
            foreach (['employer' => 'iuran_pemberi', 'employee' => 'potongan'] as $participant => $type) {
                $setting = DB::table('bpjs_settings')->where('program', 'bpjs_kes')->where('participant_type', $participant)->where('effective_from', '<=', $period->end_date)->orderByDesc('effective_from')->first();
                if (! $setting) continue;
                $amount = ceil($healthBase * (float) $setting->rate / 100);
                $componentCode = $participant === 'employer' ? 'bpjs_kesehatan_pemberi' : 'bpjs_kesehatan_pegawai';
                $component = $components->get($componentCode);
                if (! $component) continue;
                $details[] = $this->line($component, $type, $amount, $healthBase, (float) $setting->rate, 'BPJS Kes '.number_format((float) $setting->rate, 2).'%');
            }
            $extraChildren = max(0, (int) $profile->child_count - 3);
            if ($extraChildren > 0 && $components->has('bpjs_kesehatan_pegawai')) {
                $rate = (float) (DB::table('bpjs_settings')->where('program', 'bpjs_kes')->where('participant_type', 'employee')->value('rate') ?? 1);
                $amount = $extraChildren * ceil($healthBase * $rate / 100);
                $details[] = $this->line($components->get('bpjs_kesehatan_pegawai'), 'potongan', $amount, $healthBase, $rate, 'Iuran anak ke-4 dst ('.$extraChildren.' anak)');
            }
        }

        return $details;
    }

    private function componentCode(string $programCode, string $participant): string
    {
        $suffix = $participant === 'employer' ? 'pemberi' : 'pegawai';
        return match ($programCode) {
            'bpjs_tk_jkk' => 'bpjs_jkk_'.$suffix,
            'bpjs_tk_jkm' => 'bpjs_jkm_'.$suffix,
            'bpjs_tk_jht' => 'bpjs_jht_'.$suffix,
            'bpjs_tk_jp' => 'bpjs_jp_'.$suffix,
            default => 'bpjs_'.$programCode.'_'.$suffix,
        };
    }

    private function line(PayrollComponent $component, string $type, float $amount, float $basis, float $rate, string $note): array
    {
        return [
            'component_id' => $component->id,
            'type' => $type,
            'quantity' => 1,
            'unit_rate' => $rate,
            'basis_amount' => $basis,
            'amount' => $amount,
            'formula_note' => $note,
            'source_type' => 'bpjs_calculator',
            'source_id' => null,
            'sort_order' => $component->sort_order,
        ];
    }
}
