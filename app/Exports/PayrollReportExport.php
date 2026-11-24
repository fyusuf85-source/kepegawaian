<?php

namespace App\Exports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private PayrollPeriod|PayrollRun $source) {}

    public function collection(): \Illuminate\Support\Collection
    {
        $run = $this->resolveRun();

        return $run
            ? $run->employees()->with('employee:id,nip,full_name')->get()
            : collect();
    }

    public function headings(): array
    {
        return ['NIP', 'Nama Lengkap', 'Bruto', 'Potongan', 'Kontribusi Yayasan', 'Netto', 'Status'];
    }

    /** @param \App\Models\PayrollRunEmployee $row */
    public function map($row): array
    {
        return [
            $row->employee?->nip,
            $row->employee?->full_name,
            (float) $row->gross,
            (float) $row->total_deduction,
            (float) $row->employer_contribution,
            (float) $row->net,
            strtoupper($row->status),
        ];
    }

    private function resolveRun(): ?PayrollRun
    {
        if ($this->source instanceof PayrollRun) {
            return $this->source;
        }

        return $this->source->runs()
            ->where('status', '!=', 'dibatalkan')
            ->latest('version')
            ->first();
    }
}
