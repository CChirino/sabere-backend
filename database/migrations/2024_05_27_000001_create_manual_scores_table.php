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
        Schema::create('manual_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_assignment_id')->constrained('subject_assignments')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->string('title'); // Ej: "Participación", "Examen Parcial", "Exposición"
            $table->text('description')->nullable();
            $table->decimal('score', 5, 2); // Nota obtenida
            $table->decimal('max_score', 5, 2)->default(20); // Nota máxima posible
            $table->foreignId('graded_by')->constrained('users');
            $table->dateTime('graded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'subject_assignment_id', 'term_id'], 'manual_scores_student_assignment_term_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_scores');
    }
};
