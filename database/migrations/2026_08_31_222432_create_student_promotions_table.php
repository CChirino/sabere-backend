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
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->foreignId('from_grade_id')->constrained('grades');
            $table->foreignId('to_grade_id')->nullable()->constrained('grades');
            $table->string('status')->default('promoted'); // promoted, repeating, conditional
            $table->text('decision')->nullable();
            $table->foreignId('decided_by')->constrained('users');
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'academic_period_id'], 'student_promotion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
    }
};
