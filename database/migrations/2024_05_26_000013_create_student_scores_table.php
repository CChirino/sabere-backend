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
        Schema::create('student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('subject_assignment_id')->constrained('subject_assignments');
            $table->foreignId('term_id')->constrained('terms');
            $table->decimal('score', 5, 2); // Nota del lapso (escala de 20)
            $table->text('observations')->nullable(); // Observaciones del profesor
            $table->foreignId('graded_by')->constrained('users'); // Profesor que calificó
            $table->dateTime('graded_at');
            $table->boolean('is_final')->default(false); // Si es nota definitiva del lapso
            $table->timestamps();
            $table->softDeletes();

            // Una nota por estudiante, materia y lapso
            $table->unique(['student_id', 'subject_assignment_id', 'term_id'], 'student_subject_term_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_scores');
    }
};
