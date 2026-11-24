<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->boolean('requires_document')->default(false);
            $table->unsignedTinyInteger('document_required_after_days')->nullable();
            $table->boolean('affects_balance')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->unsignedSmallInteger('max_days_per_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('total_days');
            $table->text('reason');
            $table->string('document_path', 255)->nullable();
            $table->string('status', 30)->default('menunggu_atasan');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('submitted_at');
            $table->timestamp('decided_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('employee_id', 'idx_leave_requests_employee');
            $table->index(['start_date', 'end_date'], 'idx_leave_requests_range');
            $table->index('status', 'idx_leave_requests_status');
        });

        Schema::create('leave_request_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_half_day')->default(false);
            $table->timestamps();

            $table->unique(['leave_request_id', 'date'], 'uq_leave_request_days');
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('entitlement_days', 5, 1)->default(0);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('adjustment_days', 5, 1)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'uq_leave_balances');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_request_days');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};
