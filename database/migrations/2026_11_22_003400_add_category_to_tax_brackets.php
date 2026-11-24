<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->string('category', 10)->default('A')->after('tax_year');
            $table->dropUnique(['tax_year', 'min_amount']);
            $table->unique(['tax_year', 'category', 'min_amount'], 'tax_brackets_year_cat_min_uq');
        });
    }

    public function down(): void
    {
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->dropUnique('tax_brackets_year_cat_min_uq');
            $table->dropColumn('category');
            $table->unique(['tax_year', 'min_amount']);
        });
    }
};
