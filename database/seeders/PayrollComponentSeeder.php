<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

class PayrollComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['code' => 'gaji_pokok', 'name' => 'Gaji Pokok', 'type' => 'penghasilan', 'category' => 'gaji', 'calculation_method' => 'fixed', 'sort_order' => 5],
            ['code' => 'gaji_harian', 'name' => 'Gaji Harian', 'type' => 'penghasilan', 'category' => 'gaji', 'calculation_method' => 'per_day', 'sort_order' => 10],
            ['code' => 'basis_pendidikan', 'name' => 'Basis Pendidikan (SMA/D3/S1)', 'type' => 'penghasilan', 'category' => 'gaji', 'calculation_method' => 'fixed', 'sort_order' => 20],
            ['code' => 'tunjangan_pasangan', 'name' => 'Tunjangan Suami/Istri', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'fixed', 'sort_order' => 25],
            ['code' => 'tunjangan_anak', 'name' => 'Tunjangan Anak', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'per_child', 'sort_order' => 26],
            ['code' => 'tunjangan_beras', 'name' => 'Tunjangan Beras', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'per_child', 'sort_order' => 27],
            ['code' => 'tunjangan_kehadiran', 'name' => 'Tunjangan Kehadiran', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'per_minute', 'sort_order' => 28],
            ['code' => 'tunjangan_jabatan', 'name' => 'Tunjangan Jabatan', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'fixed', 'sort_order' => 30],
            ['code' => 'tunjangan_istimewa', 'name' => 'Tunjangan Istimewa', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'fixed', 'sort_order' => 31],
            ['code' => 'tunjangan_kehadiran_msu_sd', 'name' => 'Tunjangan Kehadiran MSU/SD', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'formula', 'sort_order' => 40],
            ['code' => 'transport', 'name' => 'Tunjangan Transportasi', 'type' => 'penghasilan', 'category' => 'tunjangan', 'calculation_method' => 'distance_band', 'sort_order' => 50],
            ['code' => 'uang_snack', 'name' => 'Uang Snack (Non-Driver)', 'type' => 'penghasilan', 'category' => 'honor', 'calculation_method' => 'per_day', 'sort_order' => 60],
            ['code' => 'honorarium_khidmah', 'name' => 'Honorarium Khidmah', 'type' => 'penghasilan', 'category' => 'honor', 'calculation_method' => 'fixed', 'sort_order' => 61],
            ['code' => 'potongan_alpa', 'name' => 'Potongan Alpa', 'type' => 'potongan', 'category' => 'potongan', 'calculation_method' => 'per_day', 'sort_order' => 100],
            ['code' => 'potongan_terlambat', 'name' => 'Potongan Terlambat', 'type' => 'potongan', 'category' => 'potongan', 'calculation_method' => 'per_minute', 'sort_order' => 110],
            ['code' => 'bpjs_jkk_pemberi', 'name' => 'JKK Pemberi Kerja', 'type' => 'iuran_pemberi', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 115],
            ['code' => 'bpjs_jkm_pemberi', 'name' => 'JKM Pemberi Kerja', 'type' => 'iuran_pemberi', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 116],
            ['code' => 'bpjs_jht_pemberi', 'name' => 'JHT Pemberi Kerja', 'type' => 'iuran_pemberi', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 117],
            ['code' => 'bpjs_jht_pegawai', 'name' => 'JHT Pegawai', 'type' => 'potongan', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 118],
            ['code' => 'bpjs_jkk_pegawai', 'name' => 'JKK Pegawai', 'type' => 'potongan', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 119],
            ['code' => 'bpjs_jkm_pegawai', 'name' => 'JKM Pegawai', 'type' => 'potongan', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 120],
            ['code' => 'bpjs_kesehatan_pegawai', 'name' => 'Iuran BPJS Kesehatan Pegawai', 'type' => 'potongan', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 121],
            ['code' => 'bpjs_kesehatan_pemberi', 'name' => 'Iuran BPJS Kesehatan Pemberi Kerja', 'type' => 'iuran_pemberi', 'category' => 'bpjs', 'calculation_method' => 'percentage', 'sort_order' => 130],
            ['code' => 'pph21', 'name' => 'PPh 21', 'type' => 'potongan', 'category' => 'pajak', 'calculation_method' => 'formula', 'sort_order' => 140],
        ];

        foreach ($components as $c) {
            PayrollComponent::query()->updateOrCreate(
                ['code' => $c['code']],
                array_merge($c, [
                    'effective_from' => '2026-01-01',
                    'is_taxable' => !in_array($c['type'], ['potongan', 'iuran_pemberi']),
                    'is_bpjs_base' => in_array($c['code'], ['gaji_harian', 'basis_pendidikan']),
                    'is_active' => true,
                ])
            );
        }
    }
}
