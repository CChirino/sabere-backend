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
        Schema::create('annual_averages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->decimal('average_score', 5, 2);
            $table->char('letter_grade', 1);
            $table->string('status')->default('promoted'); // promoted, repeating, conditional
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'academic_period_id'], 'annual_average_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_averages');
    }
};
