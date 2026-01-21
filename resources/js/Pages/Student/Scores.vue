<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useAuth } from '@/composables/useAuth';

interface GradedTask {
    id: number;
    title: string;
    type: string;
    max_score: number;
    score: number;
    feedback?: string;
    graded_at: string;
    subject_name: string;
    term_name?: string;
    term_id?: number;
}

const { user } = useAuth();

const gradedTasks = ref<GradedTask[]>([]);
const loading = ref(true);

const fetchGradedTasks = async () => {
    loading.value = true;
    try {
        // Fetch task submissions that are graded
        const response = await fetch(`/api/v1/task-submissions?student_id=${user.value.id}&status=graded`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        
        // Transform submissions to graded tasks
        gradedTasks.value = (data.data || []).map((sub: any) => ({
            id: sub.id,
            title: sub.task?.title || 'Tarea',
            type: sub.task?.type || 'homework',
            max_score: sub.task?.max_score || 20,
            score: sub.score,
            feedback: sub.feedback,
            graded_at: sub.graded_at,
            subject_name: sub.task?.subject_assignment?.subject?.name || 'Materia',
            term_name: sub.task?.term?.name,
            term_id: sub.task?.term_id,
        }));
    } catch (error) {
        console.error('Error fetching graded tasks:', error);
    } finally {
        loading.value = false;
    }
};

const getScoreColor = (score: number, maxScore: number): 'green' | 'yellow' | 'red' => {
    const percentage = (score / maxScore) * 100;
    if (percentage >= 80) return 'green';
    if (percentage >= 50) return 'yellow';
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

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// Group by subject
const tasksBySubject = computed(() => {
    const grouped: Record<string, GradedTask[]> = {};
    gradedTasks.value.forEach(task => {
        if (!grouped[task.subject_name]) {
            grouped[task.subject_name] = [];
        }
        grouped[task.subject_name].push(task);
    });
    return grouped;
});

const getSubjectAverage = (tasks: GradedTask[]) => {
    if (!tasks.length) return 0;
    const totalScore = tasks.reduce((acc, t) => acc + t.score, 0);
    const totalMax = tasks.reduce((acc, t) => acc + t.max_score, 0);
    return ((totalScore / totalMax) * 20).toFixed(2); // Normalize to 20 points
};

const hasGrades = computed(() => gradedTasks.value.length > 0);

onMounted(() => {
    fetchGradedTasks();
});
</script>

<template>
    <Head title="Mis Calificaciones" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Mis Calificaciones</h1>
        </template>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando calificaciones...</span>
            </div>
        </Card>

        <div v-else-if="!hasGrades" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin calificaciones</h3>
            <p class="mt-1 text-gray-500">Aún no tienes calificaciones registradas.</p>
        </div>

        <div v-else class="space-y-6">
            <Card
                v-for="(tasks, subjectName) in tasksBySubject"
                :key="subjectName"
            >
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ subjectName }}
                        </h3>
                        <div class="text-right">
                            <span class="text-sm text-gray-500">Promedio:</span>
                            <Badge :color="getScoreColor(parseFloat(getSubjectAverage(tasks)), 20)" class="ml-2">
                                {{ getSubjectAverage(tasks) }}
                            </Badge>
                        </div>
                    </div>
                </template>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Evaluación
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Tipo
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Nota
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Fecha
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Retroalimentación
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="task in tasks" :key="task.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ task.title }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <Badge color="blue" size="sm">
                                        {{ getTaskTypeLabel(task.type) }}
                                    </Badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <span :class="['text-lg font-bold', getScoreColor(task.score, task.max_score) === 'green' ? 'text-green-600' : getScoreColor(task.score, task.max_score) === 'yellow' ? 'text-yellow-600' : 'text-red-600']">
                                        {{ task.score }} / {{ task.max_score }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                                    {{ formatDate(task.graded_at) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    {{ task.feedback || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
