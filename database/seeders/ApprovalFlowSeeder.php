<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()->pluck('id', 'code');

        $flows = [
            [
                'code' => 'contract',
                'name' => 'Persetujuan Kontrak Kerja',
                'subject_type' => 'App\\Models\\EmploymentContract',
                'steps' => [
                    ['role' => 'pimpinan', 'scope' => 'all'],
                ],
            ],
            [
                'code' => 'decree',
                'name' => 'Persetujuan SK',
                'subject_type' => 'App\\Models\\Decree',
                'steps' => [
                    ['role' => 'pimpinan', 'scope' => 'all'],
                ],
            ],
            [
                'code' => 'mutation',
                'name' => 'Persetujuan Mutasi',
                'subject_type' => 'App\\Models\\Mutation',
                'steps' => [
                    ['role' => 'kepala_bagian', 'scope' => 'unit'],
                    ['role' => 'pimpinan', 'scope' => 'all'],
                ],
            ],
            [
                'code' => 'payroll',
                'name' => 'Persetujuan Periode Payroll',
                'subject_type' => 'App\\Models\\PayrollPeriod',
                'steps' => [
                    ['role' => 'keuangan', 'scope' => 'all'],
                    ['role' => 'pimpinan', 'scope' => 'all'],
                ],
            ],
            [
                'code' => 'letter',
                'name' => 'Persetujuan Surat',
                'subject_type' => 'App\\Models\\EmployeeLetter',
                'steps' => [
                    ['role' => 'pimpinan', 'scope' => 'all'],
                ],
            ],
        ];

        foreach ($flows as $definition) {
            $flow = ApprovalFlow::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'subject_type' => $definition['subject_type'],
                    'is_active' => true,
                ]
            );

            $flow->steps()->delete();

            foreach ($definition['steps'] as $index => $step) {
                $flow->steps()->create([
                    'sequence' => $index + 1,
                    'role_id' => $roles[$step['role']],
                    'scope' => $step['scope'],
                    'is_optional' => false,
                ]);
            }
        }
    }
}
