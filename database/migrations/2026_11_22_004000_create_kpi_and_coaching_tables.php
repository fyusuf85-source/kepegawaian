<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft'); // draft, open, closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->string('target_type', 20)->default('max'); // min, max
            $table->string('unit', 50)->nullable();
            $table->timestamps();
            $table->unique(['kpi_template_id', 'code']);
        });

        Schema::create('kpi_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('kpi_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft, submitted, verified, approved
            $table->decimal('final_score', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'kpi_period_id', 'kpi_template_id']);
        });

        Schema::create('kpi_assessment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_indicator_id')->constrained()->restrictOnDelete();
            $table->decimal('target', 10, 2)->nullable();
            $table->decimal('actual', 10, 2)->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('weighted_score', 8, 2)->default(0);
            $table->text('notes')->nullable();
        });

        Schema::create('coaching_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('date');
            $table->string('type', 30); // konseling, pembinaan_kedisiplinan, teguran, dll
            $table->text('reason');
            $table->text('result')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaching_records');
        Schema::dropIfExists('kpi_assessment_items');
        Schema::dropIfExists('kpi_assessments');
        Schema::dropIfExists('kpi_indicators');
        Schema::dropIfExists('kpi_templates');
        Schema::dropIfExists('kpi_periods');
    }
};
