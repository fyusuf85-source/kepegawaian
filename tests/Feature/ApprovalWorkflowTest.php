<?php

namespace Tests\Feature;

use App\Models\ApprovalInstance;
use App\Models\Decree;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\Mutation;
use App\Models\Notification;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pengaju_tidak_boleh_menyetujui_dokumennya_sendiri_dan_pimpinan_dapat_menyetujui(): void
    {
        $hrd = $this->user('hrd.demo');
        $pimpinan = $this->user('pimpinan.demo');
        $employee = $this->employee('DEMO — Bagus Setiawan');

        $contract = $this->contract($employee, $hrd, [
            'contract_number' => 'KTR/UJI/0001',
            'end_date' => now('Asia/Jakarta')->addYear()->toDateString(),
        ]);

        $this->actingAs($hrd)->post(route('contracts.submit', $contract))->assertSessionHasNoErrors();

        $instance = ApprovalInstance::query()->firstOrFail();
        $this->assertSame('menunggu', $instance->status);
        $this->assertSame('draf', $contract->fresh()->status);

        $this->actingAs($hrd)
            ->post(route('approvals.approve', $instance))
            ->assertSessionHasErrors('approval');

        $this->assertSame('draf', $contract->fresh()->status);
        $this->assertSame(0, $instance->actions()->count());

        $this->actingAs($pimpinan)
            ->post(route('approvals.approve', $instance), ['note' => 'Disetujui pimpinan'])
            ->assertSessionHasNoErrors();

        $instance->refresh();
        $contract->refresh();

        $this->assertSame('disetujui', $instance->status);
        $this->assertNotNull($instance->completed_at);
        $this->assertSame('aktif', $contract->status);
        $this->assertSame(1, $instance->actions()->count());
        $this->assertSame($pimpinan->id, $instance->actions()->first()->actor_id);
    }

    public function test_mutasi_melewati_dua_tingkat_lalu_diterapkan_dan_riwayat_penempatan_tercatat(): void
    {
        $hrd = $this->user('hrd.demo');
        $kepala = $this->user('kepala.demo');
        $pimpinan = $this->user('pimpinan.demo');
        $employee = $this->employee('DEMO — Bagus Setiawan');

        $targetUnit = OrgUnit::where('code', 'PIS-PENDIDIKAN')->firstOrFail();
        $targetPosition = Position::where('code', 'GURU')->firstOrFail();
        $effective = now('Asia/Jakarta')->addDays(10)->toDateString();

        $mutation = Mutation::create([
            'employee_id' => $employee->id,
            'mutation_type' => 'mutasi',
            'from_org_unit_id' => $employee->org_unit_id,
            'from_position_id' => $employee->position_id,
            'to_org_unit_id' => $targetUnit->id,
            'to_position_id' => $targetPosition->id,
            'effective_date' => $effective,
            'reason' => 'Kebutuhan penugasan (DEMO)',
            'status' => 'draf',
            'requested_by' => $hrd->id,
        ]);

        $this->actingAs($hrd)->post(route('mutations.submit', $mutation))->assertSessionHasNoErrors();

        $instance = ApprovalInstance::query()->firstOrFail();
        $this->assertSame(1, $instance->current_step);

        // Pimpinan bukan pejabat pada langkah 1, sehingga ditolak.
        $this->actingAs($pimpinan)
            ->post(route('approvals.approve', $instance))
            ->assertSessionHasErrors('approval');

        $this->actingAs($kepala)
            ->post(route('approvals.approve', $instance), ['note' => 'Disetujui atasan'])
            ->assertSessionHasNoErrors();

        $instance->refresh();
        $this->assertSame('menunggu', $instance->status);
        $this->assertSame(2, $instance->current_step);

        $this->actingAs($pimpinan)
            ->post(route('approvals.approve', $instance), ['note' => 'Disetujui pimpinan'])
            ->assertSessionHasNoErrors();

        $instance->refresh();
        $mutation->refresh();

        $this->assertSame('disetujui', $instance->status);
        $this->assertSame('disetujui', $mutation->status);
        $this->assertSame(2, $instance->actions()->count());

        $this->actingAs($hrd)->post(route('mutations.apply', $mutation))->assertSessionHasNoErrors();

        $mutation->refresh();
        $employee->refresh();

        $this->assertSame('diterapkan', $mutation->status);
        $this->assertSame($targetUnit->id, $employee->org_unit_id);
        $this->assertSame($targetPosition->id, $employee->position_id);

        $assignments = \App\Models\EmployeeAssignment::query()
            ->where('employee_id', $employee->id)
            ->orderBy('effective_from')
            ->get();
        $this->assertCount(2, $assignments);

        $old = $assignments->first();
        $new = $assignments->last();

        $this->assertSame(
            \Illuminate\Support\Carbon::parse($effective, 'Asia/Jakarta')->subDay()->toDateString(),
            $old->effective_to->toDateString()
        );
        $this->assertSame($effective, $new->effective_from->toDateString());
        $this->assertSame($targetUnit->id, $new->org_unit_id);

        // Menerapkan ulang harus ditolak.
        $this->actingAs($hrd)
            ->post(route('mutations.apply', $mutation))
            ->assertSessionHasErrors('mutation');
    }

    public function test_perpanjangan_kontrak_dan_pengingat_h30_tidak_duplikat(): void
    {
        $hrd = $this->user('hrd.demo');
        $pimpinan = $this->user('pimpinan.demo');
        $employee = $this->employee('DEMO — Bagus Setiawan');

        $endDate = now('Asia/Jakarta')->addDays(30)->toDateString();

        $contract = $this->contract($employee, $hrd, [
            'contract_number' => 'KTR/UJI/0100',
            'status' => 'aktif',
            'start_date' => now('Asia/Jakarta')->subYear()->toDateString(),
            'end_date' => $endDate,
        ]);

        $this->artisan('contracts:remind', ['--date' => now('Asia/Jakarta')->toDateString()])
            ->assertSuccessful();

        $contract->refresh();
        $this->assertSame('akan_berakhir', $contract->status);

        $reminders = Notification::query()
            ->where('type', 'contract_reminder')
            ->where('user_id', $hrd->id)
            ->get();

        $this->assertCount(1, $reminders);
        $this->assertSame(30, $reminders->first()->data['days_left']);
        $this->assertSame($contract->id, $reminders->first()->data['contract_id']);

        // Menjalankan ulang pada hari yang sama tidak boleh menggandakan notifikasi.
        $this->artisan('contracts:remind', ['--date' => now('Asia/Jakarta')->toDateString()])
            ->assertSuccessful();

        $this->assertSame(
            1,
            Notification::query()
                ->where('type', 'contract_reminder')
                ->where('user_id', $hrd->id)
                ->count()
        );

        // Perpanjangan dibuat sebagai draf dan tertaut ke kontrak lama.
        $this->actingAs($hrd)->post(route('contracts.renew', $contract), [
            'employee_id' => $employee->id,
            'contract_type' => 'pkwt',
            'start_date' => now('Asia/Jakarta')->addDays(31)->toDateString(),
            'end_date' => now('Asia/Jakarta')->addDays(30)->addYear()->toDateString(),
            'notes' => 'Perpanjangan DEMO',
        ])->assertSessionHasNoErrors();

        $renewal = EmploymentContract::query()
            ->where('previous_contract_id', $contract->id)
            ->firstOrFail();

        $this->assertSame('draf', $renewal->status);
        $this->assertSame($employee->id, $renewal->employee_id);
        $this->assertMatchesRegularExpression('/^KTR\/\d{4}\/\d{4}$/', $renewal->contract_number);

        // Kontrak lama baru ditandai diperpanjang setelah kontrak baru disetujui.
        $this->assertSame('akan_berakhir', $contract->fresh()->status);

        $this->actingAs($hrd)->post(route('contracts.submit', $renewal))->assertSessionHasNoErrors();

        $instance = ApprovalInstance::query()
            ->where('subject_type', $renewal->getMorphClass())
            ->where('subject_id', $renewal->id)
            ->firstOrFail();

        $this->actingAs($pimpinan)
            ->post(route('approvals.approve', $instance))
            ->assertSessionHasNoErrors();

        $this->assertSame('aktif', $renewal->fresh()->status);
        $this->assertSame('diperpanjang', $contract->fresh()->status);
    }

    public function test_kontrak_dan_sk_dapat_dibuatkan_pdf_privat(): void
    {
        Storage::fake('local');

        $hrd = $this->user('hrd.demo');
        $employee = $this->employee('DEMO — Bagus Setiawan');

        $contract = $this->contract($employee, $hrd, [
            'contract_number' => 'KTR/UJI/0200',
            'status' => 'aktif',
            'end_date' => now('Asia/Jakarta')->addYear()->toDateString(),
        ]);

        $this->actingAs($hrd)->get(route('contracts.pdf', $contract))->assertOk();

        $contract->refresh();
        $this->assertNotNull($contract->document_path);
        Storage::disk('local')->assertExists($contract->document_path);

        $decree = Decree::create([
            'employee_id' => $employee->id,
            'decree_type' => 'penempatan',
            'number' => 'SK/UJI/0200',
            'decree_date' => now('Asia/Jakarta')->toDateString(),
            'effective_date' => now('Asia/Jakarta')->toDateString(),
            'title' => 'Penempatan DEMO',
            'description' => 'Penetapan penempatan untuk pengujian.',
            'status' => 'draf',
            'created_by' => $hrd->id,
        ]);

        $this->actingAs($hrd)->get(route('decrees.pdf', $decree))->assertOk();

        $decree->refresh();
        $this->assertNotNull($decree->document_path);
        Storage::disk('local')->assertExists($decree->document_path);
    }

    public function test_otorisasi_akses_dokumen_hrd_dan_halaman_persetujuan(): void
    {
        $hrd = $this->user('hrd.demo');
        $pegawai = $this->user('pegawai.demo');
        $keuangan = $this->user('keuangan.demo');
        $admin = $this->user('admin');
        $other = $this->employee('DEMO — Ahmad Fauzan');
        $own = $pegawai->employee;

        $contract = $this->contract($other, $hrd, [
            'contract_number' => 'KTR/UJI/0300',
            'status' => 'aktif',
            'end_date' => now('Asia/Jakarta')->addYear()->toDateString(),
        ]);

        $this->actingAs($pegawai)->get(route('contracts.show', $contract))->assertForbidden();
        $this->actingAs($pegawai)->get('/kontrak/tambah')->assertForbidden();
        $this->actingAs($pegawai)->get('/rekrutmen/pelamar')->assertForbidden();

        $this->actingAs($keuangan)->get('/sk')->assertForbidden();
        $this->actingAs($keuangan)->get('/persetujuan')->assertOk();

        // Pegawai tetap dapat melihat dokumen miliknya sendiri.
        $ownContract = $this->contract($own, $hrd, [
            'contract_number' => 'KTR/UJI/0301',
            'status' => 'aktif',
            'end_date' => now('Asia/Jakarta')->addYear()->toDateString(),
        ]);

        $this->actingAs($pegawai)->get(route('contracts.show', $ownContract))->assertOk();

        $this->actingAs($admin)->get('/persetujuan')->assertOk();
        $this->actingAs($admin)->get('/notifikasi')->assertOk();
    }

    protected function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    protected function employee(string $fullName): Employee
    {
        return Employee::where('full_name', $fullName)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function contract(Employee $employee, User $actor, array $overrides = []): EmploymentContract
    {
        return EmploymentContract::create(array_merge([
            'employee_id' => $employee->id,
            'contract_type' => 'pkwt',
            'start_date' => now('Asia/Jakarta')->subMonth()->toDateString(),
            'end_date' => now('Asia/Jakarta')->addMonths(6)->toDateString(),
            'status' => 'draf',
            'created_by' => $actor->id,
        ], $overrides));
    }
}
