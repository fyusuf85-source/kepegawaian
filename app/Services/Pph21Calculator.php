<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\TaxBracket;
use Illuminate\Support\Collection;

class Pph21Calculator
{
    public function calculate(Employee $employee, object $profile, float $grossIncome, Collection $components): ?array
    {
        $component = $components->get('pph21');
        if (! $component || $grossIncome <= 0) return null;

        $category = $this->terCategory($profile->tax_status ?? null, (int) ($profile->dependents_count ?? 0));
        $rate = $this->terRate($category, $grossIncome);
        if ($rate <= 0) return null;

        $amount = ceil($grossIncome * $rate);
        return [
            'component_id' => $component->id,
            'type' => $profile->tax_borne_by_employer ? 'iuran_pemberi' : 'potongan',
            'quantity' => 1,
            'unit_rate' => $rate * 100,
            'basis_amount' => $grossIncome,
            'amount' => $amount,
            'formula_note' => 'PPh 21 TER Kategori '.$category.' ('.number_format($rate * 100, 2, ',', '.').'%)',
            'source_type' => 'pph21_calculator',
            'source_id' => null,
            'sort_order' => $component->sort_order,
        ];
    }

    public function terCategory(?string $taxStatus, int $dependents): string
    {
        $status = strtoupper(trim((string) $taxStatus));
        if ($status === '') {
            $status = $dependents <= 0 ? 'TK/0' : ($dependents === 1 ? 'TK/1' : 'K/'.$dependents);
        }
        return match ($status) {
            'TK/0', 'TK/1', 'K/0' => 'A',
            'TK/2', 'TK/3', 'K/1', 'K/2' => 'B',
            'K/3' => 'C',
            default => 'A',
        };
    }

    private function terRate(string $category, float $grossIncome): float
    {
        $bracket = TaxBracket::query()
            ->where('category', $category)
            ->where('min_amount', '<=', $grossIncome)
            ->orderByDesc('min_amount')
            ->first();
        return $bracket ? (float) $bracket->rate : 0.0;
    }
}
