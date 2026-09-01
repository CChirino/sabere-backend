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
        Schema::create('annual_subject_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->decimal('final_score', 5, 2);
            $table->char('letter_grade', 1);
            $table->string('status')->default('promoted'); // promoted, recovery, failed
            $table->boolean('is_pending')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'subject_id', 'academic_period_id'], 'annual_subject_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_subject_scores');
    }
};
