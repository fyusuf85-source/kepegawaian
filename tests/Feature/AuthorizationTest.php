<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_tanpa_permission_ditolak_membuka_daftar_pengguna(): void
    {
        $user = $this->userWithPermissions(['dashboard.pegawai']);

        $this->actingAs($user)->get('/pengguna')->assertForbidden();
    }

    public function test_pengguna_dengan_permission_dapat_membuka_daftar_pengguna(): void
    {
        $user = $this->userWithPermissions(['user.view']);

        $this->actingAs($user)->get('/pengguna')->assertOk();
    }

    public function test_hrd_dapat_membuka_form_tambah_pegawai_tetapi_pegawai_biasa_tidak(): void
    {
        $hrd = $this->userWithPermissions(['employee.create', 'employee.view.all']);
        $pegawai = $this->userWithPermissions(['employee.view.self']);

        $this->actingAs($hrd)->get('/pegawai/tambah')->assertOk();
        $this->actingAs($pegawai)->get('/pegawai/tambah')->assertForbidden();
    }

    public function test_gate_mengikuti_kode_permission(): void
    {
        $user = $this->userWithPermissions(['employee.update']);

        $this->assertTrue(Gate::forUser($user)->allows('employee.update'));
        $this->assertFalse(Gate::forUser($user)->allows('employee.delete'));
    }

    public function test_kepala_bagian_hanya_melihat_pegawai_dalam_unit_turunannya(): void
    {
        $root = \App\Models\OrgUnit::create(['code' => 'U-ROOT', 'name' => 'Root', 'type' => 'yayasan', 'level' => 1]);
        $child = \App\Models\OrgUnit::create(['code' => 'U-ANAK', 'name' => 'Anak', 'type' => 'bagian', 'level' => 2, 'parent_id' => $root->id]);
        $other = \App\Models\OrgUnit::create(['code' => 'U-LAIN', 'name' => 'Lain', 'type' => 'bagian', 'level' => 2, 'parent_id' => null]);

        $this->makeEmployee(['full_name' => 'Pegawai Dalam Unit', 'nip' => 'UJI-DALAM', 'org_unit_id' => $child->id]);
        $this->makeEmployee(['full_name' => 'Pegawai Luar Unit', 'nip' => 'UJI-LUAR', 'org_unit_id' => $other->id]);

        $kepala = $this->userWithPermissions(
            ['employee.view.unit', 'employee.view.self'],
            [],
            $root->id
        );

        $response = $this->actingAs($kepala)->get('/pegawai');

        $response->assertOk();
        $response->assertSee('Pegawai Dalam Unit');
        $response->assertDontSee('Pegawai Luar Unit');
    }

    public function test_peran_menyimpan_permission_sesuai_katalog(): void
    {
        $permission = Permission::create(['code' => 'contoh.aksi', 'name' => 'Contoh', 'module' => 'uji']);
        $role = Role::create(['code' => 'contoh_peran', 'name' => 'Contoh Peran', 'is_system' => false]);

        $role->permissions()->sync([$permission->id]);

        $this->assertTrue($role->fresh()->permissions->contains($permission));
    }
}
