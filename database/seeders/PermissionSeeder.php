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

        // Permisos para profesores
        $teacherPermissions = [
            'view teachers',
            'create teachers',
            'edit teachers',
            'delete teachers',
        ];

        // Permisos para cursos/materias
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

        // Permisos para secciones
        $sectionPermissions = [
            'view sections',
            'create sections',
            'edit sections',
            'delete sections',
        ];

        // Permisos para inscripciones
        $enrollmentPermissions = [
            'view enrollments',
            'create enrollments',
            'edit enrollments',
            'delete enrollments',
        ];

        // Permisos para tareas
        $taskPermissions = [
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'submit tasks',
        ];

        // Permisos para entregas/submissions
        $submissionPermissions = [
            'view submissions',
            'grade submissions',
        ];

        // Permisos para reportes
        $reportPermissions = [
            'view reports',
            'export reports',
        ];

        // Permisos para períodos académicos
        $academicPeriodPermissions = [
            'view academic_periods',
            'create academic_periods',
            'edit academic_periods',
            'delete academic_periods',
        ];

        // Combinar todos los permisos
        $allPermissions = array_merge(
            $userPermissions,
            $rolePermissions,
            $studentPermissions,
            $teacherPermissions,
            $coursePermissions,
            $gradePermissions,
            $sectionPermissions,
            $enrollmentPermissions,
            $taskPermissions,
            $submissionPermissions,
            $reportPermissions,
            $academicPeriodPermissions
        );

        // Crear permisos
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Permisos creados exitosamente!');
    }
}