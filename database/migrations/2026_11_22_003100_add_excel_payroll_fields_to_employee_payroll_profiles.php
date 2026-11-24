<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->decimal('last_salary_amount', 15, 2)->nullable()->after('salary_basis_override');
            $table->date('last_salary_effective_from')->nullable()->after('last_salary_amount');
            $table->boolean('spouse_status')->default(false)->after('dependents_count');
            $table->unsignedTinyInteger('child_count')->default(0)->after('spouse_status');
            $table->string('bpjs_tk_program', 20)->nullable()->after('bpjs_tk_no');
            $table->boolean('bpjs_health_participates')->default(false)->after('bpjs_kesehatan_no');
            $table->boolean('tax_borne_by_employer')->default(false)->after('tax_status');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->dropColumn(['last_salary_amount', 'last_salary_effective_from', 'spouse_status', 'child_count', 'bpjs_tk_program', 'bpjs_health_participates', 'tax_borne_by_employer']);
        });
    }
};
