<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Aturan dokumen & saldo masih sementara (K-16, A-05); dapat diubah lewat master.
        $types = [
            ['izin_pribadi', 'Izin Pribadi', false, null, false, true, null, 1],
            ['sakit', 'Sakit', true, 1, false, true, null, 2],
            ['keluarga', 'Izin Keluarga', false, null, false, true, null, 3],
            ['cuti_tahunan', 'Cuti Tahunan', false, null, true, true, 12, 4],
            ['dinas', 'Dinas Luar', false, null, false, true, null, 5],
            ['dispensasi', 'Dispensasi', false, null, false, true, null, 6],
        ];

        foreach ($types as [$code, $name, $document, $afterDays, $affects, $paid, $max, $order]) {
            LeaveType::updateOrCreate(['code' => $code], [
                'name' => $name,
                'requires_document' => $document,
                'document_required_after_days' => $afterDays,
                'affects_balance' => $affects,
                'is_paid' => $paid,
                'max_days_per_year' => $max,
                'is_active' => true,
                'sort_order' => $order,
            ]);
        }
    }
}
