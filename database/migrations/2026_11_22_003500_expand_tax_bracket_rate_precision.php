<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->decimal('rate', 8, 6)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->decimal('rate', 6, 3)->change();
        });
    }
};
