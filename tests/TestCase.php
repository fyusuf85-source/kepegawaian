<?php

namespace Tests;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<int, string>  $permissions
     */
    protected function userWithPermissions(
        array $permissions = [],
        array $attributes = [],
        ?int $orgUnitId = null
    ): User {
        $role = Role::create([
            'code' => 'test_'.Str::lower(Str::random(8)),
            'name' => 'Peran Uji',
            'is_system' => false,
        ]);

        foreach ($permissions as $code) {
            $permission = Permission::firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'module' => 'uji']
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::create(array_merge([
            'name' => 'Pengguna Uji',
            'username' => 'uji_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));

        $user->roles()->attach($role, ['org_unit_id' => $orgUnitId]);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeEmployee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'nip' => 'UJI-'.Str::upper(Str::random(8)),
            'full_name' => 'Pegawai Uji '.Str::random(4),
            'gender' => 'laki-laki',
            'marital_status' => 'lajang',
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => '2026-01-05',
            'is_active' => true,
        ], $overrides));
    }
}
