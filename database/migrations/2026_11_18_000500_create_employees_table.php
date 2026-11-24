<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->unique();
            $table->text('nik')->nullable();
            $table->char('nik_hash', 64)->nullable()->unique();
            $table->string('full_name', 150);
            $table->string('nick_name', 60)->nullable();
            $table->string('gender', 10);
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('marital_status', 20)->default('lajang');
            $table->string('blood_type', 3)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('employee_category', 20);
            $table->string('khidmah_type', 20)->nullable();
            $table->string('employment_status', 30)->default('aktif');
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->unsignedBigInteger('direct_supervisor_id')->nullable();
            $table->foreignId('work_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('termination_reason', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_category', 'employment_status']);
            $table->index('direct_supervisor_id');
            $table->index('full_name');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('direct_supervisor_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
