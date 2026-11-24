<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('relation', 20);
            $table->string('name', 150);
            $table->text('nik')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('education', 50)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->boolean('is_dependent')->default(true);
            $table->boolean('is_working_at_pis')->default(false);
            $table->date('marriage_date')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'relation']);
        });

        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('education_id')->constrained('educations')->restrictOnDelete();
            $table->string('institution', 150);
            $table->string('major', 100)->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('certificate_path', 255)->nullable();
            $table->boolean('is_highest')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('bank_name', 100);
            $table->text('account_number');
            $table->string('account_holder', 150);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employee_educations');
        Schema::dropIfExists('employee_families');
    }
};
