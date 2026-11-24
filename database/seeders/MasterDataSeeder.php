<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\Holiday;
use App\Models\NumberSequence;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Setting;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $educations = [
            ['SD', 'Sekolah Dasar', 1],
            ['SMP', 'Sekolah Menengah Pertama', 2],
            ['SMA', 'Sekolah Menengah Atas', 3],
            ['D3', 'Diploma 3', 4],
            ['S1', 'Sarjana', 5],
            ['S2', 'Magister', 6],
            ['S3', 'Doktor', 7],
        ];

        foreach ($educations as [$code, $name, $order]) {
            Education::updateOrCreate(['code' => $code], [
                'name' => $name,
                'level_order' => $order,
                'is_active' => true,
            ]);
        }

        $root = OrgUnit::updateOrCreate(['code' => 'PIS-YAYASAN'], [
            'parent_id' => null,
            'name' => 'Yayasan Pesantren Imam Syafi\'i (DEMO)',
            'type' => 'yayasan',
            'level' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $pimpinan = OrgUnit::updateOrCreate(['code' => 'PIS-PIMPINAN'], [
            'parent_id' => $root->id,
            'name' => 'Pimpinan Pesantren (DEMO)',
            'type' => 'pimpinan',
            'level' => 2,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $hrd = OrgUnit::updateOrCreate(['code' => 'PIS-HRD'], [
            'parent_id' => $pimpinan->id,
            'name' => 'Bagian HRD (DEMO)',
            'type' => 'bagian',
            'level' => 3,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $keuangan = OrgUnit::updateOrCreate(['code' => 'PIS-KEUANGAN'], [
            'parent_id' => $pimpinan->id,
            'name' => 'Bagian Keuangan (DEMO)',
            'type' => 'bagian',
            'level' => 3,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $pendidikan = OrgUnit::updateOrCreate(['code' => 'PIS-PENDIDIKAN'], [
            'parent_id' => $pimpinan->id,
            'name' => 'Bidang Pendidikan (DEMO)',
            'type' => 'bidang',
            'level' => 3,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        OrgUnit::updateOrCreate(['code' => 'PIS-SMP'], [
            'parent_id' => $pendidikan->id,
            'name' => 'SMP Islam Terpadu (DEMO)',
            'type' => 'unit_pendidikan',
            'level' => 4,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $positions = [
            ['PIMPINAN', 'Pimpinan Pesantren', $pimpinan->id, 1],
            ['KEPALA-HRD', 'Kepala Bagian HRD', $hrd->id, 2],
            ['STAF-HRD', 'Staf HRD', $hrd->id, 3],
            ['KEPALA-KEU', 'Kepala Bagian Keuangan', $keuangan->id, 2],
            ['STAF-KEU', 'Staf Keuangan', $keuangan->id, 3],
            ['KEPALA-PEND', 'Kepala Bidang Pendidikan', $pendidikan->id, 2],
            ['GURU', 'Guru', $pendidikan->id, 4],
            ['KEPALA-SMP', 'Kepala SMP', $pendidikan->id, 3],
        ];

        foreach ($positions as [$code, $name, $unitId, $level]) {
            Position::updateOrCreate(['code' => $code], [
                'name' => $name,
                'org_unit_id' => $unitId,
                'level' => $level,
                'is_active' => true,
            ]);
        }

        WorkLocation::updateOrCreate(['code' => 'PUSAT'], [
            'org_unit_id' => $root->id,
            'name' => 'Kantor Pusat (DEMO)',
            'address' => 'Koordinat contoh — ganti dengan lokasi sebenarnya',
            'latitude' => -7.4478000,
            'longitude' => 112.7183000,
            'radius_m' => 100,
            'accuracy_threshold_m' => 100,
            'is_active' => true,
        ]);

        $pagi = WorkShift::updateOrCreate(['code' => 'PAGI'], [
            'name' => 'Shift Pagi',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'is_cross_midnight' => false,
            'break_minutes' => 60,
            'late_tolerance_minutes' => 10,
            'early_leave_tolerance_minutes' => 10,
            'is_active' => true,
        ]);

        $schedule = WorkSchedule::updateOrCreate(['name' => 'Jadwal Kantor (DEMO)'], [
            'org_unit_id' => null,
            'employee_category' => null,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'is_active' => true,
        ]);

        if ($schedule->days()->count() === 0) {
            foreach (range(1, 7) as $day) {
                $schedule->days()->create([
                    'day_of_week' => $day,
                    'shift_id' => $day <= 5 ? $pagi->id : null,
                    'is_workday' => $day <= 5,
                ]);
            }
        }

        Holiday::updateOrCreate(['date' => '2026-12-31'], [
            'name' => 'DEMO — Libur Internal Pesantren',
            'type' => 'pesantren',
            'is_paid' => true,
        ]);

        $settings = [
            ['app', 'app.name', 'Aplikasi Kepegawaian PIS', 'string', 'Nama aplikasi'],
            ['attendance', 'attendance.default_radius_m', '100', 'integer', 'Radius default lokasi kerja (meter)'],
            ['attendance', 'attendance.accuracy_threshold_m', '100', 'integer', 'Akurasi GPS maksimum yang diterima (meter)'],
            ['attendance', 'attendance.max_photo_kb', '2048', 'integer', 'Ukuran maksimum foto presensi (KB)'],
            ['payroll', 'payroll.rounding_mode', 'round', 'string', 'Mode pembulatan payroll (belum menetapkan tarif)'],
            ['security', 'security.password_min_length', '8', 'integer', 'Panjang minimum kata sandi'],
            ['security', 'security.session_single_device', '0', 'boolean', 'Batasi satu sesi aktif (belum diaktifkan)'],
            ['notification', 'notification.document_expiry_days', '30', 'integer', 'Pengingat dokumen sebelum kadaluarsa (hari)'],
        ];

        foreach ($settings as [$group, $key, $value, $type, $description]) {
            Setting::updateOrCreate(['key' => $key], [
                'group' => $group,
                'value' => $value,
                'value_type' => $type,
                'description' => $description,
            ]);
        }

        NumberSequence::updateOrCreate(['code' => 'nip'], [
            'prefix' => 'PIS',
            'pattern' => '{prefix}-{year}-{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);

        NumberSequence::updateOrCreate(['code' => 'contract'], [
            'prefix' => 'KTR',
            'pattern' => '{prefix}/{year}/{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);

        NumberSequence::updateOrCreate(['code' => 'decree'], [
            'prefix' => 'SK',
            'pattern' => '{prefix}/{year}/{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);

        NumberSequence::updateOrCreate(['code' => 'letter'], [
            'prefix' => 'SRT',
            'pattern' => '{prefix}/{year}/{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);

        NumberSequence::updateOrCreate(['code' => 'vacancy'], [
            'prefix' => 'LOW',
            'pattern' => '{prefix}/{year}/{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);

        NumberSequence::updateOrCreate(['code' => 'applicant'], [
            'prefix' => 'PLM',
            'pattern' => '{prefix}/{year}/{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);
    }
}
