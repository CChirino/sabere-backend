<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { User, Task, TaskSubmission, Enrollment } from '@/types';

const props = defineProps<{
    studentId: number;
}>();

interface StudentInfo extends User {
    enrollments?: Enrollment[];
    task_submissions?: TaskSubmission[];
}

const student = ref<StudentInfo | null>(null);
const tasks = ref<Task[]>([]);
const loading = ref(true);

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
    } catch (error) {
        console.error('Error fetching student info:', error);
    }
};

const fetchTasks = async () => {
    try {
        const response = await fetch(`/api/v1/guardian/student/${props.studentId}/tasks`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        tasks.value = data.data || [];
    } catch (error) {
        console.error('Error fetching tasks:', error);
    }
};

const getActiveEnrollment = computed(() => {
    return student.value?.enrollments?.find(e => e.status === 'active');
});

const getSubmissionForTask = (taskId: number): TaskSubmission | undefined => {
    return student.value?.task_submissions?.find(s => s.task_id === taskId);
};

const getStatusBadge = (task: Task) => {
    const submission = getSubmissionForTask(task.id);
    if (submission?.score !== null && submission?.score !== undefined) {
        return { color: 'green' as const, text: `Calificado: ${submission.score}` };
    }
    if (submission) {
        return { color: 'blue' as const, text: 'Entregado' };
    }
    const dueDate = new Date(task.due_date);
    if (dueDate < new Date()) {
        return { color: 'red' as const, text: 'Vencido' };
    }
    return { color: 'yellow' as const, text: 'Pendiente' };
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

onMounted(async () => {
    await Promise.all([fetchStudentInfo(), fetchTasks()]);
    loading.value = false;
});
</script>

<template>
    <Head :title="`Tareas - ${student?.name || 'Estudiante'}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/guardian/students" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Tareas</h1>
                    <p v-if="student" class="text-sm text-gray-600">{{ student.name }}</p>
                </div>
            </div>
        </template>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando...</span>
            </div>
        </Card>

        <template v-else>
            <!-- Student Info Card -->
            <Card class="mb-6">
                <div class="flex items-center space-x-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-sabere-dark/10">
                        <span class="text-2xl font-semibold text-sabere-dark">
                            {{ student?.name?.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ student?.name }}</h2>
                        <p v-if="getActiveEnrollment" class="text-gray-600">
                            {{ getActiveEnrollment.section?.grade?.name }} - Sección {{ getActiveEnrollment.section?.name }}
                        </p>
                    </div>
                </div>
            </Card>

            <!-- Tasks -->
            <Card v-if="tasks.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Sin tareas</h3>
                <p class="mt-1 text-gray-500">No hay tareas asignadas.</p>
            </Card>

            <div v-else class="space-y-4">
                <Card v-for="task in tasks" :key="task.id">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-medium text-gray-900">{{ task.title }}</h3>
                                <Badge :color="getStatusBadge(task).color">
                                    {{ getStatusBadge(task).text }}
                                </Badge>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">
                                {{ task.subject_assignment?.subject?.name || 'Materia' }}
                            </p>
                            <p v-if="task.description" class="text-sm text-gray-500 mb-3">
                                {{ task.description.substring(0, 150) }}{{ task.description.length > 150 ? '...' : '' }}
                            </p>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
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
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </template>
    </AppLayout>
</template>
