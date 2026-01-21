<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Crear usuarios de prueba para cada rol
     */
    public function run(): void
    {
        // Usuario Administrador
        $admin = User::firstOrCreate(
            ['email' => 'admin@sabere.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Usuario Director
        $director = User::firstOrCreate(
            ['email' => 'director@sabere.com'],
            [
                'name' => 'Director García',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $director->assignRole('director');

        // Usuario Coordinador
        $coordinator = User::firstOrCreate(
            ['email' => 'coordinador@sabere.com'],
            [
                'name' => 'Coordinador Pérez',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $coordinator->assignRole('coordinator');

        // Usuario Profesor
        $teacher = User::firstOrCreate(
            ['email' => 'profesor@sabere.com'],
            [
                'name' => 'Prof. María López',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $teacher->assignRole('teacher');

        // Usuario Estudiante
        $student = User::firstOrCreate(
            ['email' => 'estudiante@sabere.com'],
            [
                'name' => 'Juan Estudiante',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $student->assignRole('student');

        // Usuario Representante
        $guardian = User::firstOrCreate(
            ['email' => 'representante@sabere.com'],
            [
                'name' => 'Pedro Representante',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $guardian->assignRole('guardian');

        $this->command->info('Usuarios de prueba creados:');
        $this->command->table(
            ['Rol', 'Email', 'Contraseña'],
            [
                ['Administrador', 'admin@sabere.com', 'password'],
                ['Director', 'director@sabere.com', 'password'],
                ['Coordinador', 'coordinador@sabere.com', 'password'],
                ['Profesor', 'profesor@sabere.com', 'password'],
                ['Estudiante', 'estudiante@sabere.com', 'password'],
                ['Representante', 'representante@sabere.com', 'password'],
            ]
        );
    }
}
