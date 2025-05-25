<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos para usuarios
        $userPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        // Permisos para roles
        $rolePermissions = [
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ];
        
        // Permisos para estudiantes
        $studentPermissions = [
            'view students',
            'create students',
            'edit students',
            'delete students',
        ];

        // Permisos para cursos
        $coursePermissions = [
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
        ];

        // Permisos para calificaciones
        $gradePermissions = [
            'view grades',
            'create grades',
            'edit grades',
            'delete grades',
        ];

        // Combinar todos los permisos
        $allPermissions = array_merge(
            $userPermissions,
            $rolePermissions,
            $studentPermissions,
            $coursePermissions,
            $gradePermissions
        );

        // Crear permisos
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Permisos creados exitosamente!');
    }
}