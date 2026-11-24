<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_hari_kerja_efektif_tidak_menghitung_akhir_pekan(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $days = app(LeaveService::class)->workingDays($employee, '2026-03-02', '2026-03-08');

        $this->assertSame([
            '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06',
        ], $days);
    }

    public function test_pengajuan_cuti_lima_hari_kerja_tersimpan_dengan_status_menunggu_atasan(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();

        $leave = app(LeaveService::class)->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-06',
            'reason' => 'Keperluan keluarga di luar kota.',
        ], $employee->user);

        $this->assertSame(5, $leave->total_days);
        $this->assertSame('menunggu_atasan', $leave->status);
        $this->assertSame(5, $leave->days()->count());
    }

    public function test_pengajuan_izin_yang_tumpang_tindih_ditolak(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();
        $service = app(LeaveService::class);

        $service->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-04',
            'reason' => 'Keperluan keluarga di luar kota.',
        ], $employee->user);

        $this->expectException(ValidationException::class);

        $service->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-04',
            'end_date' => '2026-03-06',
            'reason' => 'Pengajuan kedua yang tumpang tindih.',
        ], $employee->user);
    }

    public function test_saldo_cuti_tidak_cukup_ditolak(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();

        $this->assertSame(12.0, LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $cuti->id)
            ->where('year', 2026)
            ->firstOrFail()
            ->remainingDays());

        $this->expectException(ValidationException::class);

        app(LeaveService::class)->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-18',
            'reason' => 'Cuti panjang melebihi saldo yang tersedia.',
        ], $employee->user);
    }

    public function test_sakit_lebih_dari_satu_hari_wajib_dokumen(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $sakit = LeaveType::where('code', 'sakit')->firstOrFail();
        $service = app(LeaveService::class);

        // Satu hari masih diperbolehkan tanpa dokumen.
        $service->create($employee, [
            'leave_type_id' => $sakit->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-02',
            'reason' => 'Demam, istirahat satu hari.',
        ], $employee->user);

        $this->expectException(ValidationException::class);

        $service->create($employee, [
            'leave_type_id' => $sakit->id,
            'start_date' => '2026-03-09',
            'end_date' => '2026-03-10',
            'reason' => 'Sakit dua hari tanpa surat keterangan dokter.',
        ], $employee->user);
    }

    public function test_persetujuan_atasan_lalu_hrd_memotong_saldo_dan_membuat_absensi(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $supervisor = User::where('username', 'kepala.demo')->firstOrFail();
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();
        $service = app(LeaveService::class);

        $leave = $service->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-06',
            'reason' => 'Keperluan keluarga di luar kota.',
        ], $employee->user);

        $service->approveBySupervisor($leave, $supervisor);
        $this->assertSame('menunggu_hrd', $leave->fresh()->status);

        $service->approveByHrd($leave->fresh(), $hrd);
        $leave->refresh();

        $this->assertSame('disetujui', $leave->status);
        $this->assertNotNull($leave->decided_at);

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $cuti->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertSame(5.0, (float) $balance->used_days);
        $this->assertSame(7.0, $balance->remainingDays());

        $this->assertSame(5, Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'izin')
            ->whereBetween('work_date', ['2026-03-02', '2026-03-06'])
            ->count());
    }

    public function test_atasan_lain_tidak_dapat_menyetujui_pengajuan(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $other = User::where('username', 'hrd.demo')->firstOrFail();
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();
        $service = app(LeaveService::class);

        $leave = $service->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-03',
            'reason' => 'Keperluan keluarga di luar kota.',
        ], $employee->user);

        $this->expectException(ValidationException::class);

        $service->approveBySupervisor($leave, $other);
    }

    public function test_penolakan_hrd_menyimpan_alasan_dan_tidak_memotong_saldo(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $supervisor = User::where('username', 'kepala.demo')->firstOrFail();
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();
        $service = app(LeaveService::class);

        $leave = $service->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-03',
            'reason' => 'Keperluan keluarga di luar kota.',
        ], $employee->user);

        $service->approveBySupervisor($leave, $supervisor);
        $service->reject($leave->fresh(), $hrd, 'Dokumen tidak lengkap.');

        $leave->refresh();
        $this->assertSame('ditolak', $leave->status);
        $this->assertSame('Dokumen tidak lengkap.', $leave->rejection_reason);

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $cuti->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertSame(0.0, (float) $balance->used_days);
    }

    public function test_pengguna_tidak_berwenang_tidak_dapat_melihat_pengajuan_orang_lain(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $keuangan = User::where('username', 'keuangan.demo')->firstOrFail();
        $cuti = LeaveType::where('code', 'cuti_tahunan')->firstOrFail();

        $leave = app(LeaveService::class)->create($employee, [
            'leave_type_id' => $cuti->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-02',
            'reason' => 'Keperluan keluarga di luar kota.',
        ], $employee->user);

        $this->actingAs($keuangan)->get(route('leave.show', $leave))->assertForbidden();
    }

    public function test_hrd_dapat_membuka_halaman_saldo_dan_master_jenis_izin(): void
    {
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();

        $this->actingAs($hrd)->get(route('leave.balances.index'))->assertOk()->assertSee('Saldo Cuti');
        $this->actingAs($hrd)->get(route('master.leave-types.index'))->assertOk()->assertSee('Jenis Izin');
    }
}
