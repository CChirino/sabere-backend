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

        // Crear roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $student = Role::firstOrCreate(['name' => 'student']);
        $parent = Role::firstOrCreate(['name' => 'parent']);

        // Asignar todos los permisos al super administrador
        $superAdmin->syncPermissions(Permission::all());

        // Asignar permisos al administrador
        $adminPermissions = [
            'view users', 'create users', 'edit users',
            'view students', 'create students', 'edit students',
            'view courses', 'create courses', 'edit courses',
            'view grades', 'create grades', 'edit grades',
        ];
        $admin->syncPermissions($adminPermissions);

        // Asignar permisos al docente
        $teacherPermissions = [
            'view students',
            'view courses',
            'view grades', 'create grades', 'edit grades',
        ];
        $teacher->syncPermissions($teacherPermissions);

        // Asignar permisos al estudiante
        $student->givePermissionTo('view grades');

        // Asignar permisos al padre
        $parent->givePermissionTo('view grades');

        $this->command->info('Roles creados exitosamente!');
    }
}