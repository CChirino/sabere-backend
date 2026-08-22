<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MEJORA-03: Agrega soft_deletes a la tabla users para evitar pérdida
 *            accidental de datos al eliminar usuarios.
 * MEJORA-09: Agrega campos de identidad venezolana (cédula, teléfono, fecha de nacimiento).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // MEJORA-03: Soft deletes para preservar datos históricos de usuarios
            $table->softDeletes()->after('remember_token');

            // MEJORA-09: Campos de identidad venezolana
            // Cédula: V-12345678 (venezolano), E-12345678 (extranjero)
            $table->string('cedula', 20)->nullable()->unique()->after('email')
                ->comment('Cédula venezolana. Formato: V-12345678 o E-12345678');
            $table->string('phone', 20)->nullable()->after('cedula')
                ->comment('Número de teléfono de contacto');
            $table->date('birth_date')->nullable()->after('phone')
                ->comment('Fecha de nacimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['cedula', 'phone', 'birth_date']);
        });
    }
};
