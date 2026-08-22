<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 500)->nullable()->after('birth_date')
                ->comment('Ruta de la imagen de perfil');
            $table->text('bio')->nullable()->after('avatar')
                ->comment('Biografía o descripción del usuario');
            $table->string('address')->nullable()->after('bio')
                ->comment('Dirección de residencia');
            $table->string('emergency_contact_name')->nullable()->after('address')
                ->comment('Nombre del contacto de emergencia');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name')
                ->comment('Teléfono del contacto de emergencia');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'bio', 'address', 'emergency_contact_name', 'emergency_contact_phone']);
        });
    }
};
