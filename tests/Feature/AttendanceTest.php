<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ScheduleAssignment;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use App\Models\WorkShift;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_presensi_masuk_dalam_radius_tersimpan_dengan_foto_dan_idempotensi(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $employee = $user->employee;
        $location = $employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 06:50:00', 'Asia/Jakarta'));

        $payload = [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '15',
            'idempotency_key' => '11111111-1111-4111-8111-111111111111',
            'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ];

        $this->actingAs($user)->post(route('attendance.check-in'), $payload)->assertRedirect()->assertSessionHasNoErrors();

        $attendance = Attendance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-03-02', $attendance->work_date->toDateString());
        $this->assertSame('hadir', $attendance->status);
        $this->assertSame(0, $attendance->late_minutes);
        $this->assertNotNull($attendance->check_in_photo_path);
        Storage::disk('local')->assertExists($attendance->check_in_photo_path);
        $this->assertLessThanOrEqual(1, (float) $attendance->check_in_distance_m);

        // Kiriman ulang dengan kunci idempotensi yang sama tidak membuat baris baru.
        $this->actingAs($user)->post(route('attendance.check-in'), $payload)->assertRedirect();
        $this->assertSame(1, Attendance::query()->where('employee_id', $employee->id)->count());
    }

    public function test_presensi_di_luar_radius_ditolak(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $employee = $user->employee;
        $location = $employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 06:50:00', 'Asia/Jakarta'));

        $this->actingAs($user)->post(route('attendance.check-in'), [
            'lat' => (string) ((float) $location->latitude + 0.01),
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ])->assertSessionHasErrors('attendance');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_akurasi_gps_di_atas_ambang_ditolak(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $employee = $user->employee;
        $location = $employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 06:50:00', 'Asia/Jakarta'));

        $this->actingAs($user)->post(route('attendance.check-in'), [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '150',
            'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ])->assertSessionHasErrors('attendance');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_foto_selfie_wajib_ada_saat_presensi(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $location = $user->employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 06:50:00', 'Asia/Jakarta'));

        $this->actingAs($user)->post(route('attendance.check-in'), [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
        ])->assertSessionHasErrors('photo');
    }

    public function test_presensi_ganda_dengan_kunci_berbeda_ditolak(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $employee = $user->employee;
        $location = $employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 06:50:00', 'Asia/Jakarta'));

        $base = [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ];

        $this->actingAs($user)->post(route('attendance.check-in'), $base + [
            'idempotency_key' => '22222222-2222-4222-8222-222222222222',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('attendance.check-in'), $base + [
            'idempotency_key' => '33333333-3333-4333-8333-333333333333',
        ])->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::query()->where('employee_id', $employee->id)->count());
    }

    public function test_terlambat_pulang_awal_dan_menit_kerja_dihitung(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();
        $employee = $user->employee;
        $location = $employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 07:20:00', 'Asia/Jakarta'));
        $this->actingAs($user)->post(route('attendance.check-in'), [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('masuk.jpg', 640, 480),
        ])->assertSessionHasNoErrors();

        $attendance = Attendance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(10, $attendance->late_minutes);
        $this->assertSame('terlambat', $attendance->status);

        Carbon::setTestNow(Carbon::parse('2026-03-02 14:30:00', 'Asia/Jakarta'));
        $this->actingAs($user)->post(route('attendance.check-out'), [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('pulang.jpg', 640, 480),
        ])->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame(20, $attendance->early_leave_minutes);
        $this->assertSame(370, $attendance->work_minutes);
        $this->assertSame('terlambat', $attendance->status);
    }

    public function test_presensi_pulang_shift_lintas_tengah_malam_memakai_baris_hari_sebelumnya(): void
    {
        $employee = $this->makeEmployee([
            'work_location_id' => WorkLocation::where('code', 'PUSAT')->value('id'),
        ]);

        $user = $this->userWithPermissions(
            ['attendance.self', 'attendance.view.self'],
            ['employee_id' => $employee->id]
        );

        $shift = WorkShift::create([
            'code' => 'MALAM-UJI',
            'name' => 'Shift Malam Uji',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'is_cross_midnight' => true,
            'break_minutes' => 60,
            'late_tolerance_minutes' => 0,
            'early_leave_tolerance_minutes' => 0,
            'is_active' => true,
        ]);

        $schedule = WorkSchedule::create([
            'name' => 'Jadwal Malam Uji',
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        foreach (range(1, 7) as $day) {
            $schedule->days()->create([
                'day_of_week' => $day,
                'shift_id' => $shift->id,
                'is_workday' => true,
            ]);
        }

        ScheduleAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'effective_from' => '2026-01-01',
        ]);

        $location = $employee->workLocation;

        Carbon::setTestNow(Carbon::parse('2026-03-02 22:05:00', 'Asia/Jakarta'));
        $this->actingAs($user)->post(route('attendance.check-in'), [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('masuk.jpg', 640, 480),
        ])->assertSessionHasNoErrors();

        Carbon::setTestNow(Carbon::parse('2026-03-03 06:05:00', 'Asia/Jakarta'));
        $this->actingAs($user)->post(route('attendance.check-out'), [
            'lat' => (string) $location->latitude,
            'lng' => (string) $location->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('pulang.jpg', 640, 480),
        ])->assertSessionHasNoErrors();

        $attendance = Attendance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-03-02', $attendance->work_date->toDateString());
        $this->assertNotNull($attendance->check_out_at);
        $this->assertSame(420, $attendance->work_minutes);
        $this->assertSame(0, $attendance->early_leave_minutes);
    }

    public function test_koreksi_absensi_diproses_atasan_lalu_hrd_dan_mengubah_absensi(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $supervisor = User::where('username', 'kepala.demo')->firstOrFail();
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();

        $service = app(\App\Services\AttendanceService::class);

        $correction = $service->submitCorrection($employee, [
            'work_date' => '2026-02-27',
            'correction_type' => 'keduanya',
            'requested_check_in_at' => '07:00',
            'requested_check_out_at' => '15:00',
            'reason' => 'Lupa melakukan presensi karena tugas mendadak.',
        ], $employee->user);

        $this->assertSame('menunggu_atasan', $correction->status);
        $this->assertSame($supervisor->employee_id, $correction->supervisor_id);

        $service->approveCorrectionBySupervisor($correction, $supervisor, 'Sesuai.');
        $this->assertSame('menunggu_hrd', $correction->fresh()->status);

        $service->approveCorrectionByHrd($correction->fresh(), $hrd, 'Diverifikasi.');
        $correction->refresh();

        $this->assertSame('disetujui', $correction->status);
        $this->assertNotNull($correction->applied_at);

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '2026-02-27')
            ->firstOrFail();

        $this->assertSame('07:00', $attendance->checkInLocal()->format('H:i'));
        $this->assertSame('15:00', $attendance->checkOutLocal()->format('H:i'));
        $this->assertTrue($attendance->is_corrected);
        $this->assertSame('hadir', $attendance->status);
    }

    public function test_koreksi_pada_absensi_terkunci_payroll_ditolak(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $service = app(\App\Services\AttendanceService::class);

        Attendance::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-02-26',
            'shift_id' => WorkShift::where('code', 'PAGI')->value('id'),
            'status' => 'hadir',
            'is_locked' => true,
        ]);

        $correction = $service->submitCorrection($employee, [
            'work_date' => '2026-02-26',
            'correction_type' => 'masuk',
            'requested_check_in_at' => '07:05',
            'reason' => 'Koreksi jam masuk pada periode payroll terkunci.',
        ], $employee->user);

        $this->expectException(ValidationException::class);

        $service->approveCorrectionByHrd($correction, $hrd);
    }

    public function test_dispensasi_absensi_disetujui_hrd_menjadi_status_dispensasi(): void
    {
        $employee = User::where('username', 'pegawai.demo')->firstOrFail()->employee;
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $service = app(\App\Services\AttendanceService::class);

        $exception = $service->submitException($employee, [
            'work_date' => '2026-02-25',
            'type' => 'gps_tidak_tersedia',
            'check_in_at' => '08:00',
            'check_out_at' => '12:00',
            'reason' => 'GPS perangkat tidak tersedia saat presensi.',
        ], $employee->user);

        $service->approveException($exception, $hrd);

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '2026-02-25')
            ->firstOrFail();

        $this->assertSame('dispensasi', $attendance->status);
        $this->assertSame('08:00', $attendance->checkInLocal()->format('H:i'));
        $this->assertSame('disetujui', $exception->fresh()->status);
    }

    public function test_presensi_di_lokasi_penugasan_jadwal_berhasil(): void
    {
        $locationDefault = WorkLocation::where('code', 'PUSAT')->firstOrFail();
        $locationTugas = WorkLocation::create([
            'code' => 'TUGAS',
            'name' => 'Lokasi Tugas',
            'latitude' => '-6.1751',
            'longitude' => '106.8272',
            'radius_m' => 100,
            'is_active' => true,
        ]);

        $employee = $this->makeEmployee(['work_location_id' => $locationDefault->id]);
        $user = $this->userWithPermissions(['attendance.self', 'attendance.view.self'], ['employee_id' => $employee->id]);

        $shift = WorkShift::where('code', 'PAGI')->firstOrFail();
        $schedule = WorkSchedule::create([
            'name' => 'Jadwal Tugas',
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);
        $schedule->days()->create(['day_of_week' => 1, 'shift_id' => $shift->id, 'is_workday' => true]);

        ScheduleAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_location_id' => $locationTugas->id,
            'effective_from' => '2026-03-02',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-02 07:00:00', 'Asia/Jakarta'));

        $this->actingAs($user)->post(route('attendance.check-in'), [
            'lat' => (string) $locationTugas->latitude,
            'lng' => (string) $locationTugas->longitude,
            'accuracy_m' => '10',
            'photo' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'work_location_id' => $locationTugas->id,
        ]);
    }

    public function test_halaman_presensi_dan_riwayat_dapat_dibuka_pegawai(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();

        $this->actingAs($user)->get(route('attendance.index'))->assertOk()->assertSee('Presensi Masuk');
        $this->actingAs($user)->get(route('attendance.history'))->assertOk();
        $this->actingAs($user)->get(route('exceptions.index'))->assertOk();
        $this->actingAs($user)->get(route('corrections.index'))->assertOk();
        $this->actingAs($user)->get(route('leave.index'))->assertOk();
    }

    public function test_pegawai_tidak_dapat_membuka_rekap_absensi(): void
    {
        $user = User::where('username', 'pegawai.demo')->firstOrFail();

        $this->actingAs($user)->get(route('attendance.report'))->assertForbidden();
    }
}
