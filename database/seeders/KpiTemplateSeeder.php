<?php

namespace Database\Seeders;

use App\Models\KpiIndicator;
use App\Models\KpiTemplate;
use Illuminate\Database\Seeder;

class KpiTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = KpiTemplate::query()->updateOrCreate(
            ['name' => 'Template KPI Umum (DEMO)'],
            ['description' => 'Indikator dasar untuk evaluasi dan pembinaan; tidak berdampak pada payroll.', 'is_active' => true]
        );

        $indicators = [
            ['code' => 'K1', 'name' => 'Kehadiran & Ketepatan Waktu', 'weight' => 30, 'target_type' => 'min', 'unit' => '%'],
            ['code' => 'K2', 'name' => 'Kualitas Pelaksanaan Tugas', 'weight' => 30, 'target_type' => 'min', 'unit' => 'skor'],
            ['code' => 'K3', 'name' => 'Tanggung Jawab & Inisiatif', 'weight' => 20, 'target_type' => 'min', 'unit' => 'skor'],
            ['code' => 'K4', 'name' => 'Kerja Sama & Sikap', 'weight' => 20, 'target_type' => 'min', 'unit' => 'skor'],
        ];

        foreach ($indicators as $indicator) {
            KpiIndicator::query()->updateOrCreate(
                ['kpi_template_id' => $template->id, 'code' => $indicator['code']],
                $indicator + ['description' => 'Indikator DEMO — sesuaikan dengan kebijakan pesantren.']
            );
        }
    }
}
