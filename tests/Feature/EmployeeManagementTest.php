<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\NumberSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
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

    public function test_hrd_dapat_menambah_pegawai_dengan_nip_otomatis_dan_nik_terenkripsi(): void
    {
        $hrd = $this->userWithPermissions([
            'employee.create', 'employee.view.all', 'employee.sensitive.view',
        ]);

        $response = $this->actingAs($hrd)->post('/pegawai', [
            'full_name' => 'Pegawai Baru Uji',
            'nik' => '3200000000000001',
            'gender' => 'laki-laki',
            'marital_status' => 'lajang',
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => '2026-07-01',
            'is_active' => '1',
        ]);

        $employee = Employee::firstOrFail();

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertMatchesRegularExpression('/^PIS-\d{4}-\d{4}$/', $employee->nip);
        $this->assertSame(hash('sha256', '3200000000000001'), $employee->nik_hash);

        $rawNik = DB::table('employees')->where('id', $employee->id)->value('nik');
        $this->assertNotSame('3200000000000001', $rawNik);
        $this->assertSame('3200000000000001', $employee->nik);

        $this->assertDatabaseHas('employee_payroll_profiles', ['employee_id' => $employee->id]);
        $this->assertDatabaseHas('employee_status_histories', ['employee_id' => $employee->id]);
    }

    public function test_nik_duplikat_ditolak(): void
    {
        $hrd = $this->userWithPermissions(['employee.create']);

        $payload = [
            'full_name' => 'Pegawai Pertama',
            'nik' => '3200000000000002',
            'gender' => 'laki-laki',
            'marital_status' => 'lajang',
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => '2026-07-01',
        ];

        $this->actingAs($hrd)->post('/pegawai', $payload)->assertRedirect();
        $this->actingAs($hrd)->post('/pegawai', array_merge($payload, ['full_name' => 'Pegawai Kedua']))->assertSessionHasErrors('nik');

        $this->assertSame(1, Employee::count());
    }

    public function test_nik_disamarkan_tanpa_permission_sensitif(): void
    {
        $employee = $this->makeEmployee([
            'full_name' => 'Pegawai Sensitif',
            'nik' => '3200000000000003',
        ]);

        $viewer = $this->userWithPermissions(['employee.view.all']);

        $response = $this->actingAs($viewer)->get(route('employees.show', $employee));

        $response->assertOk();
        $response->assertDontSee('3200000000000003');
        $response->assertSee('••••');
    }

    public function test_nik_tampil_penuh_dengan_permission_sensitif(): void
    {
        $employee = $this->makeEmployee([
            'full_name' => 'Pegawai Sensitif Dua',
            'nik' => '3200000000000004',
        ]);

        $viewer = $this->userWithPermissions(['employee.view.all', 'employee.sensitive.view']);

        $this->actingAs($viewer)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('3200000000000004');
    }

    public function test_ubah_data_pegawai_mencatat_perubahan(): void
    {
        $employee = $this->makeEmployee(['full_name' => 'Pegawai Ubah']);
        $hrd = $this->userWithPermissions(['employee.update', 'employee.view.all']);

        $this->actingAs($hrd)->put(route('employees.update', $employee), [
            'full_name' => 'Pegawai Ubah Nama',
            'gender' => 'laki-laki',
            'marital_status' => 'lajang',
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => '2026-01-05',
            'phone' => '081299999999',
            'is_active' => '1',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('Pegawai Ubah Nama', $employee->full_name);
        $this->assertSame('081299999999', $employee->phone);
    }

    public function test_hapus_pegawai_membutuhkan_permission_khusus_dan_bersifat_soft_delete(): void
    {
        $employee = $this->makeEmployee(['full_name' => 'Pegawai Hapus']);
        $hrd = $this->userWithPermissions(['employee.update', 'employee.view.all']);

        $this->actingAs($hrd)->delete(route('employees.destroy', $employee))->assertForbidden();

        $admin = $this->userWithPermissions(['employee.delete']);

        $this->actingAs($admin)->delete(route('employees.destroy', $employee))->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }
}
