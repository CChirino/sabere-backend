<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles del sistema escolar
        $roles = [
            'admin' => 'Administrador del sistema',
            'director' => 'Director del colegio',
            'coordinator' => 'Coordinador académico',
            'teacher' => 'Profesor',
            'student' => 'Estudiante',
            'guardian' => 'Representante del estudiante',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Obtener roles
        $admin = Role::findByName('admin');
        $director = Role::findByName('director');
        $coordinator = Role::findByName('coordinator');
        $teacher = Role::findByName('teacher');
        $student = Role::findByName('student');
        $guardian = Role::findByName('guardian');

        // Asignar todos los permisos al administrador
        $admin->syncPermissions(Permission::all());

        // Permisos del Director (gestión total)
        $directorPermissions = [
            'view users', 'create users', 'edit users', 'delete users',
            'view students', 'create students', 'edit students', 'delete students',
            'view teachers', 'create teachers', 'edit teachers', 'delete teachers',
            'view courses', 'create courses', 'edit courses', 'delete courses',
            'view grades', 'create grades', 'edit grades',
            'view sections', 'create sections', 'edit sections', 'delete sections',
            'view enrollments', 'create enrollments', 'edit enrollments',
            'view reports', 'export reports',
        ];
        $director->syncPermissions(
            array_filter($directorPermissions, fn($p) => Permission::where('name', $p)->exists())
        );

        // Permisos del Coordinador (supervisión)
        $coordinatorPermissions = [
            'view users',
            'view students', 'edit students',
            'view teachers',
            'view courses',
            'view grades', 'edit grades',
            'view sections',
            'view enrollments', 'edit enrollments',
            'view reports',
        ];
        $coordinator->syncPermissions(
            array_filter($coordinatorPermissions, fn($p) => Permission::where('name', $p)->exists())
        );

        // Permisos del Profesor
        $teacherPermissions = [
            'view students',
            'view courses',
            'view grades', 'create grades', 'edit grades',
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks',
            'view submissions', 'grade submissions',
        ];
        $teacher->syncPermissions(
            array_filter($teacherPermissions, fn($p) => Permission::where('name', $p)->exists())
        );

        // Permisos del Estudiante
        $studentPermissions = [
            'view grades',
            'view tasks',
            'submit tasks',
        ];
        $student->syncPermissions(
            array_filter($studentPermissions, fn($p) => Permission::where('name', $p)->exists())
        );

        // Permisos del Representante (igual que estudiante, solo lectura)
        $guardianPermissions = [
            'view grades',
            'view tasks',
        ];
        $guardian->syncPermissions(
            array_filter($guardianPermissions, fn($p) => Permission::where('name', $p)->exists())
        );

        $this->command->info('Roles del sistema escolar creados exitosamente!');
        $this->command->table(
            ['Rol', 'Descripción'],
            collect($roles)->map(fn($desc, $name) => [$name, $desc])->toArray()
        );
    }
}