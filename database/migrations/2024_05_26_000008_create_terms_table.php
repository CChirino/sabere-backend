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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_period_id')->constrained('academic_periods');
            $table->string('name'); // Primer Lapso, Segundo Lapso, Tercer Lapso
            $table->unsignedTinyInteger('number'); // 1, 2, 3
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('weight', 5, 2)->default(33.33); // Peso porcentual del lapso
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Un número de lapso único por período académico
            $table->unique(['academic_period_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
