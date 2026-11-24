<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PayrollComponentSeeder::class,
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
            PayrollParameterSeeder::class,
            TaxTerSeeder::class,
            LeaveTypeSeeder::class,
            KpiTemplateSeeder::class,
            DemoDataSeeder::class,
            ApprovalFlowSeeder::class,
            HrDocumentTemplateSeeder::class,
        ]);
    }
}
