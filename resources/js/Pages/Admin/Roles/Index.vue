<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

// Los roles se cargarán desde el backend
const roles = [
    { name: 'admin', label: 'Administrador', color: 'red' as const, description: 'Acceso total al sistema' },
    { name: 'director', label: 'Director', color: 'purple' as const, description: 'Gestión del colegio' },
    { name: 'coordinator', label: 'Coordinador', color: 'indigo' as const, description: 'Coordinación académica' },
    { name: 'teacher', label: 'Profesor', color: 'blue' as const, description: 'Gestión de clases y calificaciones' },
    { name: 'student', label: 'Estudiante', color: 'green' as const, description: 'Acceso a tareas y calificaciones' },
    { name: 'guardian', label: 'Representante', color: 'yellow' as const, description: 'Seguimiento de representados' },
];
</script>

<template>
    <Head title="Roles" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Roles del Sistema</h1>
        </template>

        <Card title="Roles Disponibles">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="role in roles"
                    :key="role.name"
                    class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                >
                    <div class="flex items-center justify-between">
                        <Badge :color="role.color" size="lg">{{ role.label }}</Badge>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">{{ role.description }}</p>
                    <p class="mt-1 text-xs text-gray-400">Código: {{ role.name }}</p>
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
