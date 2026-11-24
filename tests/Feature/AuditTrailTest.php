<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\NumberSequence;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NumberSequence::create([
            'code' => 'nip',
            'prefix' => 'PIS',
            'pattern' => '{prefix}-{year}-{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);
    }

    public function test_pembuatan_pegawai_menulis_audit_dengan_nik_disembunyikan(): void
    {
        $actor = $this->userWithPermissions(['employee.create']);
        $this->actingAs($actor);

        app(EmployeeService::class)->create([
            'full_name' => 'Pegawai Audit',
            'nik' => '3200000000000021',
            'gender' => 'laki-laki',
            'marital_status' => 'lajang',
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => '2026-02-02',
        ], $actor);

        $log = AuditLog::query()
            ->where('event', 'create')
            ->where('auditable_type', \App\Models\Employee::class)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('Pegawai Audit', $log->new_values['full_name']);
        $this->assertSame('[disembunyikan]', $log->new_values['nik']);
        $this->assertSame($actor->id, $log->user_id);
    }

    public function test_perubahan_data_pegawai_menyimpan_nilai_lama_dan_baru(): void
    {
        $actor = $this->userWithPermissions(['employee.update']);
        $this->actingAs($actor);

        $employee = $this->makeEmployee(['full_name' => 'Pegawai Audit Ubah', 'phone' => '081200000000']);

        app(EmployeeService::class)->update($employee, ['phone' => '081277777777'], $actor);

        $log = AuditLog::where('event', 'update')->latest('id')->firstOrFail();

        $this->assertSame('081200000000', $log->old_values['phone']);
        $this->assertSame('081277777777', $log->new_values['phone']);
    }

    public function test_nomor_urut_tahun_dan_padding(): void
    {
        $service = app(\App\Services\NumberSequenceService::class);

        $first = $service->next('nip', now()->setDate(2026, 1, 2));
        $second = $service->next('nip', now()->setDate(2026, 1, 2));

        $this->assertSame('PIS-2026-0001', $first);
        $this->assertSame('PIS-2026-0002', $second);

        NumberSequence::where('code', 'nip')->update([
            'next_number' => 9,
            'last_reset_at' => '2025-01-01',
        ]);

        $this->assertSame('PIS-2026-0001', $service->next('nip', now()->setDate(2026, 1, 3)));
    }
}
