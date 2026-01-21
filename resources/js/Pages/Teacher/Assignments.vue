<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useAuth } from '@/composables/useAuth';
import type { SubjectAssignment } from '@/types';

const { user } = useAuth();
const assignments = ref<SubjectAssignment[]>([]);
const loading = ref(true);

const fetchAssignments = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/v1/subject-assignments/by-teacher/${user.value.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        assignments.value = data.data || [];
    } catch (error) {
        console.error('Error fetching assignments:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAssignments();
});
</script>

<template>
    <Head title="Mis Materias" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Mis Materias Asignadas</h1>
        </template>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando...</span>
            </div>
        </Card>

        <div v-else-if="assignments.length === 0" class="text-center py-12">
            <h3 class="text-lg font-medium text-gray-900">Sin asignaciones</h3>
            <p class="mt-1 text-gray-500">No tienes materias asignadas actualmente.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="assignment in assignments" :key="assignment.id">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ assignment.subject?.name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ assignment.section?.grade?.name }} - Sección {{ assignment.section?.name }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ assignment.section?.grade?.education_level?.name }}
                        </p>
                    </div>
                    <Badge :color="assignment.status ? 'green' : 'gray'">
                        {{ assignment.status ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
