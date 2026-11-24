<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_key', 80)->unique();
            $table->decimal('value', 15, 4);
            $table->string('unit', 30)->nullable();
            $table->string('description', 255)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('amount_override', 15, 2)->nullable();
            $table->string('override_reason', 255)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'component_id', 'effective_from'], 'epc_employee_component_date_uq');
        });

        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->boolean('spouse_is_employee')->default(false)->after('spouse_status');
            $table->decimal('special_allowance_amount', 15, 2)->default(0)->after('child_count');
            $table->unsignedSmallInteger('transport_days')->default(0)->after('transport_distance_km');
            $table->unsignedSmallInteger('snack_days')->default(0)->after('transport_days');
            $table->string('snack_payment_method', 20)->nullable()->after('snack_days');
            $table->boolean('annual_increase_enabled')->default(false)->after('last_salary_effective_from');
            $table->boolean('education_increase_enabled')->default(false)->after('annual_increase_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payroll_profiles', function (Blueprint $table) {
            $table->dropColumn(['spouse_is_employee', 'special_allowance_amount', 'transport_days', 'snack_days', 'snack_payment_method', 'annual_increase_enabled', 'education_increase_enabled']);
        });
        Schema::dropIfExists('employee_payroll_components');
        Schema::dropIfExists('payroll_parameters');
    }
};
