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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_level_id')->constrained('education_levels');
            $table->string('name');
            $table->unsignedTinyInteger('numeric_equivalent');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Asegurar que no haya duplicados de grados por nivel educativo
            $table->unique(['education_level_id', 'numeric_equivalent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
