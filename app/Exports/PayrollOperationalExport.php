<?php

namespace App\Exports;

use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollOperationalExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly PayrollPeriod $period, private readonly string $report) {}

    public function collection(): Enumerable
    {
        $run = $this->period->runs()->where('status', '!=', 'dibatalkan')->latest('version')->firstOrFail();
        return $run->employees()->with(['employee.payrollProfile', 'employee.bankAccounts', 'details.component'])->get()
            ->filter(fn ($row) => $this->report !== 'transfer_snack' || $this->amount($row, 'uang_snack') > 0)
            ->values()
            ->map(function ($row, $index) { $row->export_sequence = $index + 1; return $row; });
    }

    public function headings(): array
    {
        return match ($this->report) {
            'transfer_gaji' => ['No', 'NIP', 'Nama', 'Bank', 'No. Rekening', 'Nama Rekening', 'Jumlah Transfer Gaji'],
            'transfer_bsi' => ['No', 'Nama', 'No. Rekening', 'Nominal', 'Keterangan'],
            'transfer_snack' => ['No', 'NIP', 'Nama', 'Bank', 'No. Rekening', 'Nama Rekening', 'Jumlah Transfer Snack'],
            'bpjs_tk' => ['NIP', 'Nama', 'No. KPJ', 'Dasar Iuran', 'JKK Pemberi', 'JKM Pemberi', 'JHT Pegawai', 'JHT Pemberi', 'Total Iuran'],
            'bpjs_kes' => ['NIP', 'Nama', 'No. BPJS Kes', 'Dasar Iuran', 'BPJS Kes Pegawai', 'BPJS Kes Pemberi', 'Total Iuran'],
            'pajak' => ['NIP', 'Nama', 'PTKP', 'Kategori TER', 'Bruto', 'PPh 21 Dipotong', 'PPh 21 Ditanggung Pesantren'],
            default => [],
        };
    }

    public function map($row): array
    {
        $employee = $row->employee;
        $profile = $employee->payrollProfile;
        $bank = $employee->bankAccounts->firstWhere('is_primary', true) ?? $employee->bankAccounts->first();
        $snack = $this->amount($row, 'uang_snack');

        return match ($this->report) {
            'transfer_gaji' => [$this->sequence($row), $employee->nip, $employee->full_name, $bank?->bank_name, $bank?->account_number, $bank?->account_holder, max(0, (float) $row->net - $snack)],
            'transfer_bsi' => [$this->sequence($row), $bank?->account_holder ?? $employee->full_name, $bank?->account_number, max(0, (float) $row->net - $snack), 'Gaji ' . $this->period->code],
            'transfer_snack' => [$this->sequence($row), $employee->nip, $employee->full_name, $bank?->bank_name, $bank?->account_number, $bank?->account_holder, $snack],
            'bpjs_tk' => [$employee->nip, $employee->full_name, $profile?->bpjs_tk_no, $this->basis($row, 'bpjs_jht_pegawai', 'bpjs_jht_pemberi', 'bpjs_jkk_pemberi', 'bpjs_jkm_pemberi'), $this->amount($row, 'bpjs_jkk_pemberi'), $this->amount($row, 'bpjs_jkm_pemberi'), abs($this->amount($row, 'bpjs_jht_pegawai')), $this->amount($row, 'bpjs_jht_pemberi'), $this->sum($row, ['bpjs_jkk_pemberi', 'bpjs_jkm_pemberi', 'bpjs_jht_pegawai', 'bpjs_jht_pemberi'])],
            'bpjs_kes' => [$employee->nip, $employee->full_name, $profile?->bpjs_kesehatan_no, $this->basis($row, 'bpjs_kesehatan_pegawai', 'bpjs_kesehatan_pemberi'), abs($this->amount($row, 'bpjs_kesehatan_pegawai')), $this->amount($row, 'bpjs_kesehatan_pemberi'), $this->sum($row, ['bpjs_kesehatan_pegawai', 'bpjs_kesehatan_pemberi'])],
            'pajak' => [$employee->nip, $employee->full_name, $profile?->tax_status, $this->terCategory($profile?->tax_status, (int) ($profile?->dependents_count ?? 0)), $row->gross, $this->pph($row, 'potongan'), $this->pph($row, 'iuran_pemberi')],
            default => [],
        };
    }

    private function details($row): Collection { return $row->details->keyBy(fn ($detail) => $detail->component?->code.'-'.$detail->type); }
    private function amount($row, string $code): float { return (float) $row->details->filter(fn ($detail) => $detail->component?->code === $code)->sum('amount'); }
    private function sum($row, array $codes): float { return (float) $row->details->filter(fn ($detail) => in_array($detail->component?->code, $codes, true))->sum(fn ($detail) => abs((float) $detail->amount)); }
    private function basis($row, string ...$codes): ?float { foreach ($codes as $code) { $detail = $row->details->first(fn ($d) => $d->component?->code === $code); if ($detail?->basis_amount !== null) return (float) $detail->basis_amount; } return null; }
    private function pph($row, string $type): float { return abs((float) $row->details->filter(fn ($detail) => $detail->component?->code === 'pph21' && $detail->type === $type)->sum('amount')); }
    private function sequence($row): int { return (int) $row->export_sequence; }
    private function terCategory(?string $status, int $dependents): string { $status = strtoupper((string) $status); return match ($status ?: ($dependents ? 'K/'.$dependents : 'TK/0')) { 'TK/0', 'TK/1', 'K/0' => 'A', 'TK/2', 'TK/3', 'K/1', 'K/2' => 'B', 'K/3' => 'C', default => 'A' }; }
}
