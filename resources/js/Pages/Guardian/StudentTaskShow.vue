<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Task, TaskSubmission, User } from '@/types';

const props = defineProps<{
    studentId: number;
    taskId: number;
}>();

const student = ref<User | null>(null);
const task = ref<Task | null>(null);
const submission = ref<TaskSubmission | null>(null);
const loading = ref(true);
const error = ref('');

const fetchStudentInfo = async () => {
    try {
        const response = await fetch(`/api/v1/guardian/student/${props.studentId}/info`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        student.value = data.data;
    } catch (err) {
        console.error('Error fetching student info:', err);
    }
};

const fetchTaskDetail = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await fetch(`/api/v1/guardian/student/${props.studentId}/tasks/${props.taskId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        if (!response.ok) {
            error.value = data.message || 'Error al cargar la tarea';
            return;
        }
        task.value = data.data?.task || null;
        submission.value = data.data?.submission || null;
    } catch (err) {
        console.error('Error fetching task detail:', err);
        error.value = 'Error al cargar la tarea';
    } finally {
        loading.value = false;
    }
};

const formatDate = (date?: string) => {
    if (!date) return 'Sin fecha';
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
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

const getTaskTypeColor = (type: string): 'blue' | 'red' | 'yellow' | 'purple' | 'green' => {
    const colors: Record<string, 'blue' | 'red' | 'yellow' | 'purple' | 'green'> = {
        homework: 'blue',
        exam: 'red',
        quiz: 'yellow',
        project: 'purple',
        activity: 'green',
    };
    return colors[type] || 'blue';
};

const getStatusColor = (status?: string): 'gray' | 'yellow' | 'green' | 'red' => {
    if (!status || status === 'pending') return 'gray';
    if (status === 'submitted' || status === 'late') return 'yellow';
    if (status === 'graded') return 'green';
    return 'red';
};

const getStatusLabel = (status?: string) => {
    const labels: Record<string, string> = {
        pending: 'Pendiente',
        submitted: 'Entregada',
        late: 'Entrega tardía',
        graded: 'Calificada',
        returned: 'Devuelta',
    };
    return labels[status || 'pending'] || 'Pendiente';
};

const isOverdue = computed(() => {
    if (!task.value?.due_date) return false;
    return new Date(task.value.due_date) < new Date();
});

onMounted(async () => {
    await Promise.all([fetchStudentInfo(), fetchTaskDetail()]);
});
</script>

<template>
    <Head :title="task?.title || 'Detalle de Tarea'" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="`/guardian/students/${studentId}/tasks`" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ task?.title || 'Detalle de Tarea' }}</h1>
                    <p v-if="student" class="text-sm text-gray-600">{{ student.name }}</p>
                </div>
            </div>
        </template>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando tarea...</span>
            </div>
        </Card>

        <Card v-else-if="error" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Error</h3>
            <p class="mt-1 text-gray-500">{{ error }}</p>
        </Card>

        <template v-else-if="task">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <Badge :color="getTaskTypeColor(task.type)" size="lg">
                                {{ getTaskTypeLabel(task.type) }}
                            </Badge>
                            <span class="text-gray-500">{{ task.subject_assignment?.subject?.name }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-500">Prof. {{ task.subject_assignment?.teacher?.name }}</span>
                        </div>

                        <div v-if="task.description" class="mb-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Descripción</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ task.description }}</p>
                            </div>
                        </div>

                        <div v-if="task.instructions" class="mb-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 flex items-center">
                                <svg class="w-3 h-3 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Instrucciones
                            </h4>
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                <p class="text-sm text-blue-800 whitespace-pre-wrap">{{ task.instructions }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-4 text-sm text-gray-500 pt-4 border-t">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Vence: {{ formatDate(task.due_date) }}
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Máx: {{ task.max_score }} pts
                            </span>
                            <Badge v-if="isOverdue" color="red">Vencida</Badge>
                        </div>
                    </Card>

                    <Card v-if="submission">
                        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Entrega del estudiante
                        </h3>

                        <div v-if="submission.content" class="mb-4">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Respuesta</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ submission.content }}</p>
                            </div>
                        </div>

                        <div v-if="submission.status === 'graded'" class="bg-green-50 border border-green-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-green-800">Calificación</h4>
                                <div class="text-right">
                                    <span class="text-4xl font-bold text-green-600">{{ submission.score }}</span>
                                    <span class="text-xl text-green-500"> / {{ task.max_score }}</span>
                                </div>
                            </div>
                            <div v-if="submission.feedback" class="mt-4 pt-4 border-t border-green-200">
                                <h5 class="text-sm font-medium text-green-700 mb-2">Retroalimentación del profesor:</h5>
                                <p class="text-green-800 whitespace-pre-wrap">{{ submission.feedback }}</p>
                            </div>
                        </div>

                        <div v-else class="text-center py-4">
                            <Badge :color="getStatusColor(submission.status)" size="lg">
                                {{ getStatusLabel(submission.status) }}
                            </Badge>
                            <p v-if="submission.submitted_at" class="mt-2 text-sm text-gray-500">
                                Entregada el {{ formatDate(submission.submitted_at) }}
                            </p>
                        </div>
                    </Card>

                    <Card v-else>
                        <div class="text-center py-6">
                            <Badge color="gray" size="lg">Pendiente</Badge>
                            <p class="mt-2 text-sm text-gray-500">El estudiante aún no ha entregado esta tarea.</p>
                        </div>
                    </Card>
                </div>

                <div class="lg:col-span-1">
                    <Card>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Estado</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500">Estado de entrega</p>
                                <Badge :color="getStatusColor(submission?.status)" size="lg" class="mt-1">
                                    {{ getStatusLabel(submission?.status) }}
                                </Badge>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Fecha de vencimiento</p>
                                <p class="text-sm font-medium text-gray-900">{{ formatDate(task.due_date) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Puntaje máximo</p>
                                <p class="text-sm font-medium text-gray-900">{{ task.max_score }} pts</p>
                            </div>
                            <div v-if="submission?.score != null">
                                <p class="text-xs text-gray-500">Calificación</p>
                                <p class="text-2xl font-bold text-green-600">{{ submission.score }} <span class="text-sm text-gray-500">/ {{ task.max_score }}</span></p>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
