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
        Schema::create('evaluation_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->foreignId('term_id')->constrained('terms');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('grade_id')->constrained('grades');
            $table->foreignId('section_id')->nullable()->constrained('sections');
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['academic_period_id', 'term_id', 'subject_id', 'grade_id', 'section_id'],
                'eval_plan_unique'
            );
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_plans');
    }
};
