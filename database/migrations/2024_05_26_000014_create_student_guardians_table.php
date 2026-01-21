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
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('users'); // Representante
            $table->foreignId('student_id')->constrained('users'); // Estudiante
            $table->enum('relationship', ['father', 'mother', 'guardian', 'grandparent', 'sibling', 'other'])->default('guardian');
            $table->boolean('is_primary')->default(false); // Representante principal
            $table->boolean('can_pickup')->default(true); // Puede retirar al estudiante
            $table->boolean('emergency_contact')->default(false); // Contacto de emergencia
            $table->string('phone')->nullable(); // Teléfono de contacto
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Un representante solo puede estar vinculado una vez a un estudiante
            $table->unique(['guardian_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
