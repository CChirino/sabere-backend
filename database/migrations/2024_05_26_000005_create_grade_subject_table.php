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
        Schema::create('grade_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->string('school_year', 9); // Formato: 2024-2025
            $table->unsignedTinyInteger('hours_per_week');
            $table->boolean('is_optional')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Asegurar que no se dupliquen materias en el mismo grado y año escolar
            $table->unique(['grade_id', 'subject_id', 'school_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_subject');
    }
};
