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
        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('section_id')->constrained('sections');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Un profesor asignado a una materia en una sección por período
            $table->unique(['subject_id', 'section_id', 'academic_period_id'], 'subject_section_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_assignments');
    }
};
