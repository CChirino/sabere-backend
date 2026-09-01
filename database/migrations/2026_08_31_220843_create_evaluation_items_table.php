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
        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_plan_id')->constrained('evaluation_plans')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // exam, quiz, project, homework, participation, other
            $table->string('evaluation_mode'); // qualitative, quantitative
            $table->decimal('weight', 5, 2);
            $table->decimal('max_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->date('evaluation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_items');
    }
};
