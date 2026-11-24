<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('contract_number', 100)->unique();
            $table->string('contract_type', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('status', 20)->default('draf');
            $table->string('document_path', 255)->nullable();
            $table->date('signed_date')->nullable();
            $table->foreignId('previous_contract_id')->nullable()
                ->constrained('employment_contracts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('end_date', 'idx_contracts_end_date');
            $table->index('employee_id', 'idx_contracts_employee');
            $table->index('status', 'idx_contracts_status');
        });

        Schema::create('decrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('decree_type', 40);
            $table->string('number', 100)->unique();
            $table->date('decree_date');
            $table->date('effective_date');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('document_path', 255)->nullable();
            $table->string('status', 20)->default('draf');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'decree_type'], 'idx_decrees_employee_type');
            $table->index('status', 'idx_decrees_status');
        });

        Schema::create('mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('mutation_type', 30);
            $table->foreignId('from_org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('to_org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('from_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('to_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('from_supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('to_supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('effective_date');
            $table->string('reason', 255);
            $table->foreignId('decree_id')->nullable()->constrained('decrees')->nullOnDelete();
            $table->string('status', 20)->default('draf');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status'], 'idx_mutations_employee_status');
            $table->index('effective_date', 'idx_mutations_effective');
        });

        Schema::create('employee_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('letter_type', 40);
            $table->string('number', 100)->unique();
            $table->date('letter_date');
            $table->string('subject', 255);
            $table->longText('body')->nullable();
            $table->string('document_path', 255)->nullable();
            $table->string('status', 20)->default('draf');
            // FK coaching_records ditambahkan pada Tahap 6 saat tabel tersebut dibuat.
            $table->unsignedBigInteger('coaching_record_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'letter_type'], 'idx_letters_employee_type');
            $table->index('status', 'idx_letters_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_letters');
        Schema::dropIfExists('mutations');
        Schema::dropIfExists('decrees');
        Schema::dropIfExists('employment_contracts');
    }
};
