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
        Schema::table('disciplinary_records', function (Blueprint $table) {
            $table->index(['academic_period_id', 'severity', 'student_id'], 'disciplinary_period_severity_student_idx');
            $table->index(['academic_period_id', 'incident_type_id'], 'disciplinary_period_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disciplinary_records', function (Blueprint $table) {
            $table->dropIndex('disciplinary_period_severity_student_idx');
            $table->dropIndex('disciplinary_period_type_idx');
        });
    }
};
