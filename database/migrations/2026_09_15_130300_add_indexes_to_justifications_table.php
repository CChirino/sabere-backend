<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('justifications', function (Blueprint $table) {
            $table->index(['academic_period_id', 'status', 'student_id', 'guardian_id'], 'justifications_period_status_student_guardian_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('justifications', function (Blueprint $table) {
            $table->dropIndex('justifications_period_status_student_guardian_idx');
        });
    }
};
