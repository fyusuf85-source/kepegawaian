<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_halaman_utama_admin_dapat_dibuka(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Dasbor');
    }

    public function test_halaman_master_dan_sistem_dapat_dibuka_admin(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $paths = [
            '/pegawai',
            '/master/org-units',
            '/master/positions',
            '/master/work-locations',
            '/master/educations',
            '/master/work-shifts',
            '/master/holidays',
            '/jadwal-kerja',
            '/penugasan-jadwal',
            '/pengguna',
            '/pengguna/tambah',
            '/peran',
            '/audit',
            '/riwayat-login',
            '/pengaturan',
        ];

        foreach ($paths as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_hrd_dapat_membuka_detail_pegawai_dan_form(): void
    {
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $employee = Employee::query()->where('full_name', 'like', 'DEMO — Ahmad%')->firstOrFail();

        $this->actingAs($hrd)->get('/pegawai/tambah')->assertOk();
        $this->actingAs($hrd)->get(route('employees.show', $employee))->assertOk()->assertSee($employee->nip);
        $this->actingAs($hrd)->get(route('employees.edit', $employee))->assertOk();
        $this->actingAs($hrd)->get('/pegawai/impor')->assertOk();
    }

    public function test_halaman_rekrutmen_dan_administrasi_hrd_dapat_dibuka(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $paths = [
            '/rekrutmen/lowongan',
            '/rekrutmen/lowongan/tambah',
            '/rekrutmen/pelamar',
            '/rekrutmen/pelamar/tambah',
            '/kontrak',
            '/kontrak/tambah',
            '/sk',
            '/sk/tambah',
            '/surat',
            '/persetujuan',
            '/notifikasi',
            '/master/document-templates',
        ];

        foreach ($paths as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }

        $hrd = User::where('username', 'hrd.demo')->firstOrFail();

        foreach (['/mutasi', '/mutasi/tambah', '/surat/tambah'] as $path) {
            $this->actingAs($hrd)->get($path)->assertOk();
        }
    }

    public function test_halaman_absensi_dan_izin_cuti_dapat_dibuka(): void
    {
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();

        $paths = [
            '/presensi',
            '/presensi/riwayat',
            '/presensi/rekap',
            '/koreksi-absensi',
            '/koreksi-absensi/tambah',
            '/dispensasi-absensi',
            '/dispensasi-absensi/tambah',
            '/izin-cuti',
            '/izin-cuti/tambah',
            '/saldo-cuti',
            '/master/leave-types',
        ];

        foreach ($paths as $path) {
            $this->actingAs($hrd)->get($path)->assertOk();
        }

        $pegawai = User::where('username', 'pegawai.demo')->firstOrFail();

        foreach ([
            '/presensi',
            '/presensi/riwayat',
            '/koreksi-absensi',
            '/koreksi-absensi/tambah',
            '/dispensasi-absensi',
            '/dispensasi-absensi/tambah',
            '/izin-cuti',
            '/izin-cuti/tambah',
        ] as $path) {
            $this->actingAs($pegawai)->get($path)->assertOk();
        }
    }

    public function test_pegawai_hanya_melihat_profil_sendiri(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $employee = $user->employee;

        $this->actingAs($user)->get('/profil')->assertOk()->assertSee($employee->full_name);
        $this->actingAs($user)->get('/pegawai')->assertOk();
        $this->actingAs($user)->get('/pegawai/tambah')->assertForbidden();
        $this->actingAs($user)->get('/pengguna')->assertForbidden();
        $this->actingAs($user)->get('/audit')->assertForbidden();
    }
}
