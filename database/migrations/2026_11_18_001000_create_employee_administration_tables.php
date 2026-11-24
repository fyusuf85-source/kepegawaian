<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('doc_type', 50);
            $table->string('number', 100)->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path', 255);
            $table->boolean('is_confidential')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'doc_type']);
            $table->index('expiry_date');
        });

        Schema::create('employee_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status', 30);
            $table->date('effective_date');
            $table->string('reason', 255);
            $table->string('document_path', 255)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
        });

        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->constrained('org_units')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->unsignedBigInteger('direct_supervisor_id')->nullable();
            $table->foreignId('work_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('decision_number', 100)->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->foreign('direct_supervisor_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::create('employee_payroll_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            $table->decimal('transport_distance_km', 6, 2)->nullable();
            $table->string('position_allowance_class', 20)->nullable();
            $table->text('bpjs_kesehatan_no')->nullable();
            $table->text('bpjs_tk_no')->nullable();
            $table->text('npwp')->nullable();
            $table->string('tax_status', 10)->nullable();
            $table->unsignedTinyInteger('dependents_count')->default(0);
            $table->decimal('salary_basis_override', 15, 2)->nullable();
            $table->string('override_reason', 255)->nullable();
            $table->date('effective_from');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_profiles');
        Schema::dropIfExists('employee_assignments');
        Schema::dropIfExists('employee_status_histories');
        Schema::dropIfExists('employee_documents');
    }
};
