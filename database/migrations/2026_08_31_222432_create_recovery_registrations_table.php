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
        Schema::create('recovery_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->string('status')->default('pending'); // pending, passed, failed
            $table->decimal('recovery_score', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'subject_id', 'academic_period_id'], 'recovery_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_registrations');
    }
};
