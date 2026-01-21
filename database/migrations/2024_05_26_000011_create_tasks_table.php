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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained('subject_assignments');
            $table->foreignId('term_id')->constrained('terms');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('type', ['homework', 'exam', 'quiz', 'project', 'activity'])->default('homework');
            $table->decimal('max_score', 5, 2)->default(20.00); // Nota máxima (Venezuela usa escala de 20)
            $table->decimal('weight', 5, 2)->default(0); // Peso porcentual en el lapso
            $table->dateTime('due_date')->nullable();
            $table->dateTime('available_from')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
