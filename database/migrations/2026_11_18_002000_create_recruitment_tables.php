<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('title', 150);
            $table->foreignId('org_unit_id')->constrained('org_units')->restrictOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('employee_category', 20);
            $table->unsignedSmallInteger('quota')->default(1);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->date('open_date');
            $table->date('close_date');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'open_date'], 'idx_vacancies_status_open');
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('full_name', 150);
            $table->text('nik')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->string('source', 50)->nullable();
            $table->string('status', 30)->default('baru');
            $table->decimal('final_score', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('converted_employee_id')->nullable()->unique()
                ->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vacancy_id', 'status'], 'idx_applicants_vacancy_status');
        });

        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->string('doc_type', 50);
            $table->string('file_path', 255);
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['applicant_id', 'doc_type'], 'idx_applicant_docs_type');
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->string('stage', 30);
            $table->timestamp('scheduled_at');
            $table->foreignId('interviewer_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('mode', 20)->default('tatap_muka');
            $table->string('location', 255)->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('result', 20)->nullable();
            $table->string('status', 20)->default('dijadwalkan');
            $table->timestamps();

            $table->index(['applicant_id', 'scheduled_at'], 'idx_interviews_applicant_time');
        });

        Schema::create('applicant_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->string('criterion', 100);
            $table->decimal('weight', 6, 3);
            $table->decimal('score', 6, 2);
            $table->string('notes', 255)->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['applicant_id', 'criterion'], 'idx_applicant_scores_criterion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_scores');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('applicant_documents');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('job_vacancies');
    }
};
