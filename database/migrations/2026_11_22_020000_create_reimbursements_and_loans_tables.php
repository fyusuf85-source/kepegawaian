<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('title', 150);
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->enum('status', ['menunggu_atasan', 'menunggu_hrd', 'disetujui', 'ditolak'])->default('menunggu_atasan');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by_supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_by_supervisor_at')->nullable();
            $table->foreignId('approved_by_hrd_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_by_hrd_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('tenor_months')->default(1);
            $table->decimal('monthly_installment', 12, 2);
            $table->text('reason');
            $table->enum('status', ['menunggu_atasan', 'menunggu_hrd', 'disetujui', 'ditolak'])->default('menunggu_atasan');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by_supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_by_supervisor_at')->nullable();
            $table->foreignId('approved_by_hrd_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_by_hrd_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
        Schema::dropIfExists('reimbursements');
    }
};
