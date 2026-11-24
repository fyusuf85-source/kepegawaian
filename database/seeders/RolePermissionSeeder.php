<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/permissions.php');

        $roles = [
            'administrator' => ['Administrator', 'Mengelola sistem, pengguna, dan peran; akses payroll dibatasi sesuai matriks.'],
            'pimpinan' => ['Pimpinan', 'Melihat seluruh data dan melakukan persetujuan akhir.'],
            'kepala_bagian' => ['Kepala Bidang/Bagian', 'Mengelola dan melihat data unit kerja beserta turunannya.'],
            'hrd' => ['HRD', 'Mengelola data pegawai, administrasi, dan verifikasi.'],
            'keuangan' => ['Keuangan', 'Mengelola payroll, BPJS, pajak, dan laporan keuangan.'],
            'pegawai' => ['Pegawai', 'Mengakses data dan dokumen milik sendiri.'],
        ];

        foreach ($roles as $code => [$name, $description]) {
            Role::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description, 'is_system' => true]
            );
        }

        foreach ($data['permissions'] as $permission) {
            Permission::updateOrCreate(
                ['code' => $permission['code']],
                ['name' => $permission['name'], 'module' => $permission['module']]
            );
        }

        $permissionIds = Permission::query()->pluck('id', 'code');

        foreach ($data['roles'] as $roleCode => $codes) {
            $role = Role::where('code', $roleCode)->first();
            if (! $role) {
                continue;
            }

            $ids = collect($codes)
                ->map(fn (string $code) => $permissionIds[$code] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }
    }
}
