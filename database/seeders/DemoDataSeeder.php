<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Role;
use App\Models\ScheduleAssignment;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use App\Services\EmployeeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('code', 'administrator')->firstOrFail();
        $hrdRole = Role::where('code', 'hrd')->firstOrFail();
        $keuanganRole = Role::where('code', 'keuangan')->firstOrFail();
        $pimpinanRole = Role::where('code', 'pimpinan')->firstOrFail();
        $kepalaRole = Role::where('code', 'kepala_bagian')->firstOrFail();
        $pegawaiRole = Role::where('code', 'pegawai')->firstOrFail();

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator Sistem',
                'email' => 'admin@demo.pis.local',
                'password' => Hash::make('Admin#2026'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $admin->roles()->sync([$adminRole->id => ['org_unit_id' => null]]);

        $employees = $this->seedEmployees($admin);

        $accounts = [
            ['hrd.demo', 'DEMO — HRD', 'hrd@demo.pis.local', $hrdRole, $employees['hrd'], null],
            ['keuangan.demo', 'DEMO — Keuangan', 'keuangan@demo.pis.local', $keuanganRole, $employees['keuangan'], null],
            ['pimpinan.demo', 'DEMO — Pimpinan', 'pimpinan@demo.pis.local', $pimpinanRole, $employees['pimpinan'], null],
            ['kepala.demo', 'DEMO — Kepala Bidang', 'kepala@demo.pis.local', $kepalaRole, $employees['kepala'], OrgUnit::where('code', 'PIS-PENDIDIKAN')->value('id')],
            ['pegawai.demo', 'DEMO — Pegawai', 'pegawai@demo.pis.local', $pegawaiRole, $employees['pegawai'], null],
        ];

        foreach ($accounts as [$username, $name, $email, $role, $employee, $unitId]) {
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('Demo#2026'),
                    'employee_id' => $employee->id,
                    'is_active' => true,
                    'must_change_password' => false,
                ]
            );

            $user->roles()->sync([$role->id => ['org_unit_id' => $unitId]]);
        }

        $this->seedAttendanceSetup($employees);
    }

    /**
     * @param  array<string, Employee>  $employees
     */
    protected function seedAttendanceSetup(array $employees): void
    {
        $employees['pegawai']->update(['direct_supervisor_id' => $employees['kepala']->id]);
        $employees['kepala']->update(['direct_supervisor_id' => $employees['pimpinan']->id]);
        $employees['hrd']->update(['direct_supervisor_id' => $employees['pimpinan']->id]);
        $employees['keuangan']->update(['direct_supervisor_id' => $employees['pimpinan']->id]);

        $schedule = WorkSchedule::query()->where('name', 'Jadwal Kantor (DEMO)')->first();

        if (! $schedule) {
            return;
        }

        foreach ($employees as $employee) {
            ScheduleAssignment::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $schedule->id,
                    'effective_from' => '2026-01-01',
                ],
                [
                    'effective_to' => null,
                    'created_by' => User::query()->where('username', 'admin')->value('id'),
                ]
            );
        }

        $cuti = LeaveType::query()->where('code', 'cuti_tahunan')->first();

        if ($cuti) {
            foreach ($employees as $employee) {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $cuti->id,
                        'year' => 2026,
                    ],
                    [
                        'entitlement_days' => $cuti->max_days_per_year ?? 12,
                        'used_days' => 0,
                        'adjustment_days' => 0,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, Employee>
     */
    protected function seedEmployees(User $admin): array
    {
        $service = app(EmployeeService::class);
        $s1 = Education::where('code', 'S1')->value('id');
        $location = WorkLocation::where('code', 'PUSAT')->value('id');

        $definitions = [
            'hrd' => [
                'full_name' => 'DEMO — Ahmad Fauzan',
                'gender' => 'laki-laki',
                'birth_place' => 'Sidoarjo',
                'birth_date' => '1992-03-12',
                'phone' => '081200000001',
                'email' => 'ahmad.demo@pis.local',
                'employee_category' => 'tetap',
                'employment_status' => 'aktif',
                'org_unit_code' => 'PIS-HRD',
                'position_code' => 'STAF-HRD',
                'hire_date' => '2024-07-01',
            ],
            'keuangan' => [
                'full_name' => 'DEMO — Siti Aisyah',
                'gender' => 'perempuan',
                'birth_place' => 'Sidoarjo',
                'birth_date' => '1994-08-21',
                'phone' => '081200000002',
                'email' => 'siti.demo@pis.local',
                'employee_category' => 'tetap',
                'employment_status' => 'aktif',
                'org_unit_code' => 'PIS-KEUANGAN',
                'position_code' => 'STAF-KEU',
                'hire_date' => '2023-08-01',
            ],
            'pimpinan' => [
                'full_name' => 'DEMO — Muhammad Ridwan',
                'gender' => 'laki-laki',
                'birth_place' => 'Gresik',
                'birth_date' => '1980-01-05',
                'phone' => '081200000003',
                'email' => 'ridwan.demo@pis.local',
                'employee_category' => 'tetap',
                'employment_status' => 'aktif',
                'org_unit_code' => 'PIS-PIMPINAN',
                'position_code' => 'PIMPINAN',
                'hire_date' => '2020-07-01',
            ],
            'kepala' => [
                'full_name' => 'DEMO — Nur Hidayah',
                'gender' => 'perempuan',
                'birth_place' => 'Surabaya',
                'birth_date' => '1986-11-17',
                'phone' => '081200000004',
                'email' => 'nur.demo@pis.local',
                'employee_category' => 'tetap',
                'employment_status' => 'aktif',
                'org_unit_code' => 'PIS-PENDIDIKAN',
                'position_code' => 'KEPALA-PEND',
                'hire_date' => '2022-07-01',
            ],
            'pegawai' => [
                'full_name' => 'DEMO — Bagus Setiawan',
                'gender' => 'laki-laki',
                'birth_place' => 'Malang',
                'birth_date' => '1997-05-09',
                'phone' => '081200000005',
                'email' => 'bagus.demo@pis.local',
                'employee_category' => 'tetap',
                'employment_status' => 'aktif',
                'org_unit_code' => 'PIS-SMP',
                'position_code' => 'GURU',
                'hire_date' => '2025-01-06',
            ],
        ];

        $result = [];

        foreach ($definitions as $key => $definition) {
            $unitCode = $definition['org_unit_code'];
            $positionCode = $definition['position_code'];
            unset($definition['org_unit_code'], $definition['position_code']);

            $definition['org_unit_id'] = OrgUnit::where('code', $unitCode)->value('id');
            $definition['position_id'] = Position::where('code', $positionCode)->value('id');
            $definition['work_location_id'] = $location;
            $definition['education_id'] = $s1;
            $definition['dependents_count'] = 0;
            $definition['marital_status'] = 'menikah';

            $employee = Employee::where('full_name', $definition['full_name'])->first();

            if (! $employee) {
                $employee = $service->create($definition, $admin);
            }

            $result[$key] = $employee;
        }

        return $result;
    }
}
