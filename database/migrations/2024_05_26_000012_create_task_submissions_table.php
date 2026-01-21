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
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks');
            $table->foreignId('student_id')->constrained('users');
            $table->text('content')->nullable(); // Respuesta del estudiante
            $table->string('file_path')->nullable(); // Archivo adjunto
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable(); // Nota obtenida
            $table->text('feedback')->nullable(); // Retroalimentación del profesor
            $table->foreignId('graded_by')->nullable()->constrained('users'); // Profesor que calificó
            $table->dateTime('graded_at')->nullable();
            $table->enum('status', ['pending', 'submitted', 'late', 'graded', 'returned'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            // Un estudiante solo puede tener una entrega por tarea
            $table->unique(['task_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_submissions');
    }
};
