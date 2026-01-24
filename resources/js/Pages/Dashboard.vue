<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useAuth } from '@/composables/useAuth';
import type { DashboardData, Task, SubjectAssignment, StudentScore } from '@/types';

const props = defineProps<{
    dashboardData: DashboardData;
}>();

const { isAdmin, isDirector, isCoordinator, isTeacher, isStudent, isGuardian, roleLabel } = useAuth();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const getScoreColor = (score: number) => {
    if (score >= 16) return 'green';
    if (score >= 10) return 'yellow';
    return 'red';
};

const getTaskTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        homework: 'Tarea',
        exam: 'Examen',
        quiz: 'Quiz',
        project: 'Proyecto',
        activity: 'Actividad',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Dashboard</h1>
        </template>

        <!-- Período actual -->
        <div v-if="dashboardData.current_period" class="mb-4 sm:mb-6">
            <div class="rounded-lg bg-blue-50 p-3 sm:p-4">
                <div class="flex items-start sm:items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-xs sm:text-sm font-medium text-blue-800">
                            <span class="block sm:inline">Período: <strong>{{ dashboardData.current_period.name }}</strong></span>
                            <span v-if="dashboardData.current_term" class="block sm:inline sm:ml-2">
                                <span class="hidden sm:inline">|</span> Lapso: <strong>{{ dashboardData.current_term.name }}</strong>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Dashboard -->
        <template v-if="isAdmin">
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 mb-6 sm:mb-8">
                <StatCard title="Total Usuarios" :value="dashboardData.stats.total_users || 0" color="blue">
                    <template #icon>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </template>
                </StatCard>
                <StatCard title="Estudiantes" :value="dashboardData.stats.total_students || 0" color="green">
                    <template #icon>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                        </svg>
                    </template>
                </StatCard>
                <StatCard title="Profesores" :value="dashboardData.stats.total_teachers || 0" color="purple">
                    <template #icon>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </template>
                </StatCard>
                <StatCard title="Representantes" :value="dashboardData.stats.total_guardians || 0" color="yellow">
                    <template #icon>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </template>
                </StatCard>
                <StatCard title="Secciones" :value="dashboardData.stats.total_sections || 0" color="blue">
                    <template #icon>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </template>
                </StatCard>
                <StatCard title="Inscripciones Activas" :value="dashboardData.stats.total_enrollments || 0" color="green">
                    <template #icon>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatCard>
            </div>
        </template>

        <!-- Director Dashboard -->
        <template v-else-if="isDirector">
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4 mb-6 sm:mb-8">
                <StatCard title="Estudiantes" :value="dashboardData.stats.total_students || 0" color="green" />
                <StatCard title="Profesores" :value="dashboardData.stats.total_teachers || 0" color="purple" />
                <StatCard title="Secciones" :value="dashboardData.stats.total_sections || 0" color="blue" />
                <StatCard title="Inscripciones" :value="dashboardData.stats.active_enrollments || 0" color="yellow" />
            </div>
        </template>

        <!-- Coordinator Dashboard -->
        <template v-else-if="isCoordinator">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-5 mb-6 sm:mb-8">
                <StatCard title="Profesores" :value="dashboardData.stats.total_teachers || 0" color="purple" />
                <StatCard title="Estudiantes" :value="dashboardData.stats.total_students || 0" color="green" />
                <StatCard title="Secciones" :value="dashboardData.stats.total_sections || 0" color="blue" />
            </div>

            <Card title="Secciones" v-if="dashboardData.sections?.length">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="section in dashboardData.sections"
                        :key="section.id"
                        class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                    >
                        <h4 class="font-medium text-gray-900">
                            {{ section.grade?.name }} - {{ section.name }}
                        </h4>
                        <p class="text-sm text-gray-500">
                            {{ section.grade?.education_level?.name }}
                        </p>
                        <p class="mt-2 text-sm">
                            <span class="font-medium">{{ section.enrollments_count }}</span> estudiantes
                        </p>
                    </div>
                </div>
            </Card>
        </template>

        <!-- Teacher Dashboard -->
        <template v-else-if="isTeacher">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-5 mb-6 sm:mb-8">
                <StatCard title="Mis Materias" :value="dashboardData.stats.total_assignments || 0" color="blue" />
                <StatCard title="Mis Estudiantes" :value="dashboardData.stats.total_students || 0" color="green" />
                <StatCard title="Entregas Pendientes" :value="dashboardData.stats.pending_submissions || 0" color="yellow" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Mis Materias -->
                <Card title="Mis Materias Asignadas">
                    <div v-if="dashboardData.assignments?.length" class="space-y-3">
                        <div
                            v-for="assignment in dashboardData.assignments"
                            :key="assignment.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3"
                        >
                            <div>
                                <h4 class="font-medium text-gray-900">{{ assignment.subject?.name }}</h4>
                                <p class="text-sm text-gray-500">
                                    {{ assignment.section?.grade?.name }} - {{ assignment.section?.name }}
                                </p>
                            </div>
                            <Badge color="blue">{{ assignment.section?.grade?.education_level?.name }}</Badge>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No tienes materias asignadas</p>
                </Card>

                <!-- Tareas Próximas -->
                <Card title="Tareas Próximas a Vencer">
                    <div v-if="dashboardData.upcoming_tasks?.length" class="space-y-3">
                        <div
                            v-for="task in dashboardData.upcoming_tasks"
                            :key="task.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3"
                        >
                            <div>
                                <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                                <p class="text-sm text-gray-500">
                                    {{ task.subject_assignment?.subject?.name }}
                                </p>
                            </div>
                            <div class="text-right">
                                <Badge :color="new Date(task.due_date!) < new Date() ? 'red' : 'yellow'">
                                    {{ formatDate(task.due_date!) }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No hay tareas próximas</p>
                </Card>
            </div>
        </template>

        <!-- Student Dashboard -->
        <template v-else-if="isStudent">
            <template v-if="dashboardData.enrollment">
                <div class="mb-6 rounded-lg bg-white p-4 shadow">
                    <h3 class="text-lg font-medium text-gray-900">Mi Inscripción</h3>
                    <p class="text-gray-600">
                        {{ dashboardData.enrollment.section?.grade?.name }} - Sección {{ dashboardData.enrollment.section?.name }}
                        <span class="text-gray-400">|</span>
                        {{ dashboardData.enrollment.section?.grade?.education_level?.name }}
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-5 mb-6 sm:mb-8">
                    <StatCard title="Tareas Pendientes" :value="dashboardData.stats.pending_tasks || 0" color="yellow" />
                    <StatCard title="Promedio Actual" :value="dashboardData.stats.current_average || '-'" color="blue" />
                    <StatCard title="Materias" :value="dashboardData.stats.total_subjects || 0" color="purple" />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Tareas Pendientes -->
                    <Card title="Tareas Pendientes">
                        <div v-if="dashboardData.pending_tasks?.length" class="space-y-3">
                            <div
                                v-for="task in dashboardData.pending_tasks"
                                :key="task.id"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-3"
                            >
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                                    <p class="text-sm text-gray-500">{{ task.subject_assignment?.subject?.name }}</p>
                                </div>
                                <div class="text-right">
                                    <Badge color="gray">{{ getTaskTypeLabel(task.type) }}</Badge>
                                    <p v-if="task.due_date" class="mt-1 text-xs text-gray-500">
                                        Vence: {{ formatDate(task.due_date) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-center py-4">¡No tienes tareas pendientes!</p>
                    </Card>

                    <!-- Calificaciones -->
                    <Card title="Mis Calificaciones">
                        <div v-if="dashboardData.current_scores?.length" class="space-y-3">
                            <div
                                v-for="score in dashboardData.current_scores"
                                :key="score.id"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-3"
                            >
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ score.title }}</h4>
                                    <p class="text-sm text-gray-500">{{ score.subject_assignment?.subject?.name }}</p>
                                </div>
                                <div class="text-right">
                                    <span :class="['text-lg font-bold', (score.score / score.max_score) >= 0.5 ? 'text-green-600' : 'text-red-600']">
                                        {{ score.score }} / {{ score.max_score }}
                                    </span>
                                    <Badge color="blue" class="ml-2">{{ getTaskTypeLabel(score.type) }}</Badge>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-center py-4">Aún no hay calificaciones</p>
                    </Card>
                </div>
            </template>
            <template v-else>
                <Card>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">Sin inscripción activa</h3>
                        <p class="mt-1 text-gray-500">Contacta a la administración para inscribirte.</p>
                    </div>
                </Card>
            </template>
        </template>

        <!-- Guardian Dashboard -->
        <template v-else-if="isGuardian">
            <div class="mb-6">
                <StatCard title="Mis Representados" :value="dashboardData.stats.total_students || 0" color="blue" />
            </div>

            <Card title="Mis Representados">
                <div v-if="dashboardData.students?.length" class="space-y-4">
                    <div
                        v-for="item in dashboardData.students"
                        :key="item.student.id"
                        class="rounded-lg border border-gray-200 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900">{{ item.student.name }}</h4>
                                <p v-if="item.enrollment" class="text-sm text-gray-500">
                                    {{ item.enrollment.section?.grade?.name }} - Sección {{ item.enrollment.section?.name }}
                                </p>
                                <p v-else class="text-sm text-red-500">Sin inscripción activa</p>
                            </div>
                            <div class="text-right">
                                <div v-if="item.current_average !== null" class="mb-2">
                                    <span class="text-sm text-gray-500">Promedio:</span>
                                    <Badge :color="getScoreColor(item.current_average)" class="ml-2">
                                        {{ item.current_average }}
                                    </Badge>
                                </div>
                                <div v-if="item.pending_tasks > 0">
                                    <Badge color="yellow">{{ item.pending_tasks }} tareas pendientes</Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-gray-500 text-center py-4">No tienes estudiantes registrados</p>
            </Card>
        </template>
    </AppLayout>
</template>
