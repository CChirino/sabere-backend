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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('section_id')->constrained('sections');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->date('enrollment_date');
            $table->enum('status', ['active', 'inactive', 'transferred', 'graduated', 'withdrawn'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Un estudiante solo puede estar inscrito una vez por período académico
            $table->unique(['student_id', 'academic_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
