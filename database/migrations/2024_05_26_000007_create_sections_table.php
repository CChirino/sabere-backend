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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->string('name', 10); // A, B, C, etc.
            $table->unsignedSmallInteger('capacity')->nullable(); // Capacidad máxima de estudiantes
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Una sección única por grado y período académico
            $table->unique(['grade_id', 'academic_period_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
