<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
    <Head title="Boleta de Calificaciones" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Boleta de Calificaciones</h1>
        </template>

        <Card class="mb-4 sm:mb-6">
            <p class="text-sm sm:text-base text-gray-600">
                Las calificaciones se calculan automáticamente basándose en las evaluaciones y tareas calificadas.
                Selecciona una materia para ver el resumen de notas.
            </p>
        </Card>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando...</span>
            </div>
        </Card>

        <Card v-else-if="assignments.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin asignaciones</h3>
            <p class="mt-1 text-gray-500">No tienes materias asignadas.</p>
        </Card>

        <div v-else class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div 
                v-for="assignment in assignments" 
                :key="assignment.id" 
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-200"
            >
                <!-- Header con gradiente -->
                <div class="bg-gradient-to-r from-sabere-primary to-sabere-dark px-5 py-4 min-h-[80px]">
                    <h3 class="text-lg font-semibold">
                        {{ assignment.subject?.name || 'Materia sin nombre' }}
                    </h3>
                    <p class="text-sm mt-1">
                        {{ assignment.section?.grade?.name || 'Grado' }} - Sección {{ assignment.section?.name || '?' }}
                    </p>
                </div>
                
                <!-- Body -->
                <div class="px-5 py-4">
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ assignment.academic_period?.school_year || assignment.academic_period?.name || 'Período actual' }}
                    </div>
                    
                    <Link
                        :href="route('teacher.scores.assignment', assignment.id)"
                        class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-sabere-dark text-white rounded-lg font-medium text-sm hover:bg-sabere-dark/90 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Ver Boleta
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
