<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->foreignId('schedule_assignment_id')->nullable()
                ->constrained('schedule_assignments')->nullOnDelete();

            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('check_in_accuracy_m', 8, 2)->nullable();
            $table->decimal('check_in_distance_m', 8, 2)->nullable();
            $table->string('check_in_photo_path', 255)->nullable();
            $table->string('check_in_ip', 45)->nullable();
            $table->string('check_in_user_agent', 255)->nullable();
            $table->char('check_in_idempotency_key', 36)->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->decimal('check_out_accuracy_m', 8, 2)->nullable();
            $table->decimal('check_out_distance_m', 8, 2)->nullable();
            $table->string('check_out_photo_path', 255)->nullable();
            $table->string('check_out_ip', 45)->nullable();
            $table->string('check_out_user_agent', 255)->nullable();
            $table->char('check_out_idempotency_key', 36)->nullable();

            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->unsignedSmallInteger('work_minutes')->default(0);
            $table->string('status', 30)->default('hadir');
            $table->string('source', 20)->default('self');
            $table->boolean('is_corrected')->default(false);
            $table->boolean('is_locked')->default(false);
            // FK payroll_periods ditambahkan pada Tahap 5 saat tabel tersebut dibuat.
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date', 'shift_id'], 'uq_attendances_employee_date_shift');
            $table->unique('check_in_idempotency_key', 'uq_attendances_check_in_key');
            $table->unique('check_out_idempotency_key', 'uq_attendances_check_out_key');
            $table->index('work_date', 'idx_attendances_date');
            $table->index('payroll_period_id', 'idx_attendances_period');
            $table->index('status', 'idx_attendances_status');
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()
                ->constrained('attendances')->nullOnDelete();
            $table->date('work_date');
            $table->string('correction_type', 30);
            $table->timestamp('requested_check_in_at')->nullable();
            $table->timestamp('requested_check_out_at')->nullable();
            $table->text('reason');
            $table->string('evidence_path', 255)->nullable();
            $table->string('status', 30)->default('menunggu_atasan');
            $table->foreignId('supervisor_id')->nullable()
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('supervisor_decision_at')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->foreignId('hrd_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hrd_decision_at')->nullable();
            $table->text('hrd_note')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date'], 'idx_corrections_employee_date');
            $table->index('status', 'idx_corrections_status');
        });

        Schema::create('attendance_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->string('type', 30);
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->text('reason');
            $table->string('evidence_path', 255)->nullable();
            $table->string('status', 30)->default('menunggu');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date'], 'idx_exceptions_employee_date');
            $table->index('status', 'idx_exceptions_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_exceptions');
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendances');
    }
};
