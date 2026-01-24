<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';

const reports = [
    {
        id: 'teachers-performance',
        name: 'Rendimiento de Profesores',
        description: 'Resumen de tareas creadas, notas registradas y entregas pendientes por profesor',
        icon: 'users',
    },
    {
        id: 'sections-progress',
        name: 'Progreso por Sección',
        description: 'Estado de avance académico de cada sección con promedios y asistencia',
        icon: 'chart-bar',
    },
    {
        id: 'students-at-risk',
        name: 'Estudiantes en Riesgo',
        description: 'Listado de estudiantes con promedios por debajo de 10 puntos',
        icon: 'exclamation',
    },
    {
        id: 'attendance-summary',
        name: 'Resumen de Asistencia',
        description: 'Estadísticas de asistencia por sección y materia',
        icon: 'calendar',
    },
];

const generating = ref<string | null>(null);
const showComingSoon = ref(false);

const generateReport = async (reportId: string) => {
    // Por ahora mostrar mensaje de próximamente
    showComingSoon.value = true;
    setTimeout(() => {
        showComingSoon.value = false;
    }, 3000);
};
</script>

<template>
    <Head title="Reportes" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Reportes</h1>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card v-for="report in reports" :key="report.id">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg v-if="report.icon === 'users'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else-if="report.icon === 'chart-bar'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <svg v-else-if="report.icon === 'exclamation'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg v-else-if="report.icon === 'calendar'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">{{ report.name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ report.description }}</p>
                            <button
                                @click="generateReport(report.id)"
                                :disabled="generating === report.id"
                                class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="generating === report.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ generating === report.id ? 'Generando...' : 'Generar Reporte' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Mensaje de próximamente -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="showComingSoon" class="fixed bottom-4 right-4 bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">Función próximamente disponible</span>
            </div>
        </transition>

        <!-- Nota informativa -->
        <Card class="mt-6">
            <div class="p-4 bg-yellow-50 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="font-medium text-yellow-900">Próximamente</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            La generación de reportes en PDF estará disponible próximamente.
                            Estamos trabajando para ofrecerte esta funcionalidad.
                        </p>
                    </div>
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
