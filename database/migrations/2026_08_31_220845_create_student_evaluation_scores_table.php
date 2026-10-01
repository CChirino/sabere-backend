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
        Schema::create('student_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('subject_assignment_id')->constrained('subject_assignments');
            $table->foreignId('evaluation_item_id')->constrained('evaluation_items')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->char('letter_grade', 1)->nullable();
            $table->foreignId('graded_by')->constrained('users');
            $table->dateTime('graded_at');
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['student_id', 'subject_assignment_id', 'evaluation_item_id'],
                'student_eval_item_unique'
            );
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_evaluation_scores');
    }
};
