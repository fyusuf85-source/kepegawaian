<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('org_units', function (Blueprint $table) {
            $table->foreign('leader_employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->dropForeign(['employee_id']);
        });

        Schema::table('org_units', function (Blueprint $table) {
            $table->dropForeign(['leader_employee_id']);
        });
    }
};
