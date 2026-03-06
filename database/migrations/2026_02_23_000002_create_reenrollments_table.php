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
        Schema::create('reenrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('current_enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('target_academic_period_id')->constrained('academic_periods')->onDelete('cascade');
            $table->foreignId('target_grade_id')->constrained('grades')->onDelete('cascade');
            $table->foreignId('target_section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('student_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('target_academic_period_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reenrollments');
    }
};
