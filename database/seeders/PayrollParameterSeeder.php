<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentRate;
use App\Models\PayrollEducationBase;
use App\Models\PayrollParameter;
use App\Models\PayrollTransportBand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollParameterSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            ['parameter_key' => 'days_per_month', 'value' => 30, 'unit' => 'hari', 'description' => 'Dasar hari kerja sebulan'],
            ['parameter_key' => 'minutes_per_day', 'value' => 480, 'unit' => 'menit', 'description' => '8 jam kerja per hari'],
            ['parameter_key' => 'minutes_per_month', 'value' => 14400, 'unit' => 'menit', 'description' => '30 hari x 480 menit'],
            ['parameter_key' => 'daily_rate_harian', 'value' => 51000, 'unit' => 'idr', 'description' => 'Upah pegawai harian per hari'],
            ['parameter_key' => 'spouse_allowance', 'value' => 200000, 'unit' => 'idr', 'description' => 'Tunjangan suami/istri'],
            ['parameter_key' => 'child_allowance', 'value' => 100000, 'unit' => 'idr', 'description' => 'Tunjangan anak per orang'],
            ['parameter_key' => 'rice_allowance_per_member', 'value' => 70000, 'unit' => 'idr', 'description' => 'Tunjangan beras per anggota keluarga'],
            ['parameter_key' => 'attendance_full_standard', 'value' => 540000, 'unit' => 'idr', 'description' => 'Kehadiran penuh standar'],
            ['parameter_key' => 'attendance_full_msu_sd', 'value' => 270000, 'unit' => 'idr', 'description' => 'Kehadiran penuh unit MSU/SD'],
            ['parameter_key' => 'snack_per_day', 'value' => 2500, 'unit' => 'idr', 'description' => 'Uang snack per hari'],
            ['parameter_key' => 'khidmah_honorarium', 'value' => 700000, 'unit' => 'idr', 'description' => 'Honorarium khidmah tetap'],
            ['parameter_key' => 'bpjs_kes_base', 'value' => 4200000, 'unit' => 'idr', 'description' => 'Dasar iuran BPJS Kesehatan'],
            ['parameter_key' => 'umr_aceh_besar', 'value' => 3932552, 'unit' => 'idr', 'description' => 'UMR Aceh Besar sebagai dasar minimal'],
            ['parameter_key' => 'annual_increase_rate', 'value' => 0.05, 'unit' => 'percent', 'description' => 'Kenaikan berkala 5%'],
            ['parameter_key' => 'education_increase_rate', 'value' => 0.15, 'unit' => 'percent', 'description' => 'Kenaikan pendidikan 15%'],
        ];

        foreach ($params as $p) {
            PayrollParameter::query()->updateOrCreate(['parameter_key' => $p['parameter_key']], array_merge($p, ['effective_from' => '2026-01-01']));
        }

        $edus = [
            'SMP' => 500000,
            'SMA' => 700000,
            'D3' => 900000,
            'S1' => 1100000,
        ];

        foreach ($edus as $code => $amount) {
            $edu = Education::where('code', $code)->first();
            if ($edu) {
                PayrollEducationBase::query()->updateOrCreate(
                    ['education_id' => $edu->id, 'effective_from' => '2026-01-01'],
                    ['base_amount' => $amount]
                );
            }
        }

        $bands = [
            ['min_km' => 0, 'max_km' => 1, 'amount' => 0],
            ['min_km' => 1.01, 'max_km' => 5, 'amount' => 5000],
            ['min_km' => 5.01, 'max_km' => 10, 'amount' => 10000],
            ['min_km' => 10.01, 'max_km' => 15, 'amount' => 15000],
            ['min_km' => 15.01, 'max_km' => 20, 'amount' => 20000],
            ['min_km' => 20.01, 'max_km' => 999, 'amount' => 20000],
        ];

        foreach ($bands as $b) {
            PayrollTransportBand::query()->updateOrCreate(
                ['min_km' => $b['min_km'], 'effective_from' => '2026-01-01'],
                ['max_km' => $b['max_km'], 'amount' => $b['amount']]
            );
        }

        $bpjs = [
            ['program' => 'bpjs_tk_jkk', 'participant_type' => 'employer', 'rate' => 0.24, 'description' => 'JKK ditanggung pesantren'],
            ['program' => 'bpjs_tk_jkm', 'participant_type' => 'employer', 'rate' => 0.30, 'description' => 'JKM ditanggung pesantren'],
            ['program' => 'bpjs_tk_jht', 'participant_type' => 'employer', 'rate' => 3.7, 'description' => 'JHT ditanggung pesantren'],
            ['program' => 'bpjs_tk_jht', 'participant_type' => 'employee', 'rate' => 2.0, 'description' => 'JHT dipotong pegawai'],
            ['program' => 'bpjs_tk_jp', 'participant_type' => 'employer', 'rate' => 2.0, 'description' => 'JP ditanggung pesantren'],
            ['program' => 'bpjs_tk_jp', 'participant_type' => 'employee', 'rate' => 1.0, 'description' => 'JP dipotong pegawai'],
            ['program' => 'bpjs_kes', 'participant_type' => 'employer', 'rate' => 4.0, 'description' => 'BPJS Kes ditanggung pesantren'],
            ['program' => 'bpjs_kes', 'participant_type' => 'employee', 'rate' => 1.0, 'description' => 'BPJS Kes dipotong pegawai'],
        ];

        foreach ($bpjs as $b) {
            DB::table('bpjs_settings')->updateOrInsert(
                ['program' => $b['program'], 'participant_type' => $b['participant_type'], 'effective_from' => '2026-01-01'],
                ['rate' => $b['rate'], 'effective_to' => null]
            );
        }
    }
}
