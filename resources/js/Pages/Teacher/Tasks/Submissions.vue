<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import type { Task, TaskSubmission } from '@/types';

const props = defineProps<{
    taskId: number;
}>();

const task = ref<Task | null>(null);
const submissions = ref<TaskSubmission[]>([]);
const loading = ref(true);
const gradingSubmission = ref<TaskSubmission | null>(null);
const gradeValue = ref<number | null>(null);
const feedback = ref('');
const saving = ref(false);

const fetchTask = async () => {
    try {
        const response = await fetch(`/api/v1/tasks/${props.taskId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        task.value = data.data;
    } catch (error) {
        console.error('Error fetching task:', error);
    }
};

const fetchSubmissions = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/v1/task-submissions?task_id=${props.taskId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        submissions.value = data.data || [];
    } catch (error) {
        console.error('Error fetching submissions:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status: string): 'gray' | 'yellow' | 'green' | 'red' => {
    switch (status) {
        case 'submitted': return 'yellow';
        case 'graded': return 'green';
        case 'returned': return 'red';
        default: return 'gray';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'submitted': return 'Pendiente';
        case 'graded': return 'Calificada';
        case 'returned': return 'Devuelta';
        default: return status;
    }
};

const openGrading = (submission: TaskSubmission) => {
    gradingSubmission.value = submission;
    gradeValue.value = submission.score || null;
    feedback.value = submission.feedback || '';
};

const closeGrading = () => {
    gradingSubmission.value = null;
    gradeValue.value = null;
    feedback.value = '';
};

const submitGrade = async () => {
    if (!gradingSubmission.value || gradeValue.value === null) return;
    saving.value = true;
    
    try {
        await axios.post(`/api/v1/task-submissions/${gradingSubmission.value.id}/grade`, {
            score: gradeValue.value,
            feedback: feedback.value,
        });
        
        closeGrading();
        await fetchSubmissions();
    } catch (error) {
        console.error('Error grading submission:', error);
    } finally {
        saving.value = false;
    }
};

const pendingCount = computed(() => submissions.value.filter(s => s.status === 'submitted').length);
const gradedCount = computed(() => submissions.value.filter(s => s.status === 'graded').length);
const averageScore = computed(() => {
    const graded = submissions.value.filter(s => s.score !== null);
    if (graded.length === 0) return null;
    const sum = graded.reduce((acc, s) => acc + (s.score || 0), 0);
    return (sum / graded.length).toFixed(2);
});

onMounted(async () => {
    await fetchTask();
    await fetchSubmissions();
});
</script>

<template>
    <Head :title="`Entregas - ${task?.title || 'Tarea'}`" />

    <AppLayout>
        <template #header>
            <div>
                <Link :href="route('teacher.tasks.show', taskId)" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver a la Tarea
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">
                    Entregas
                    <span v-if="task" class="text-gray-500 font-normal">- {{ task.title }}</span>
                </h1>
            </div>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card class="text-center">
                <div class="text-3xl font-bold text-gray-900">{{ submissions.length }}</div>
                <div class="text-sm text-gray-500">Total Entregas</div>
            </Card>
            <Card class="text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ pendingCount }}</div>
                <div class="text-sm text-gray-500">Pendientes</div>
            </Card>
            <Card class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ gradedCount }}</div>
                <div class="text-sm text-gray-500">Calificadas</div>
            </Card>
            <Card class="text-center">
                <div class="text-3xl font-bold text-sabere-primary">{{ averageScore || '-' }}</div>
                <div class="text-sm text-gray-500">Promedio</div>
            </Card>
        </div>

        <!-- Loading -->
        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando entregas...</span>
            </div>
        </Card>

        <!-- No submissions -->
        <Card v-else-if="submissions.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin entregas</h3>
            <p class="mt-1 text-gray-500">Aún no hay entregas para esta tarea.</p>
        </Card>

        <!-- Submissions Table -->
        <Card v-else :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estudiante
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha de Entrega
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Calificación
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="submission in submissions" :key="submission.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-sabere-primary/10 rounded-full flex items-center justify-center">
                                        <span class="text-sabere-primary font-medium">
                                            {{ submission.student?.name?.charAt(0) }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ submission.student?.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ submission.student?.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                {{ formatDate(submission.submitted_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <Badge :color="getStatusColor(submission.status)">
                                    {{ getStatusLabel(submission.status) }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span v-if="submission.score !== null" class="text-lg font-semibold" :class="submission.score >= (task?.max_score || 20) * 0.5 ? 'text-green-600' : 'text-red-600'">
                                    {{ submission.score }} / {{ task?.max_score }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button
                                    @click="openGrading(submission)"
                                    class="text-sabere-accent hover:text-sabere-dark font-medium text-sm"
                                >
                                    {{ submission.status === 'graded' ? 'Editar Nota' : 'Calificar' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Grading Panel -->
        <div v-if="gradingSubmission" class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-gray-500 bg-opacity-75" @click="closeGrading"></div>
            <div class="absolute inset-y-0 right-0 max-w-md w-full bg-white shadow-xl">
                <div class="h-full flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900">Calificar Entrega</h2>
                        <button @click="closeGrading" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Student Info -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Estudiante</h3>
                            <p class="mt-1 text-lg text-gray-900">{{ gradingSubmission.student?.name }}</p>
                        </div>

                        <!-- Submission Date -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Fecha de Entrega</h3>
                            <p class="mt-1 text-gray-900">{{ formatDate(gradingSubmission.submitted_at) }}</p>
                        </div>

                        <!-- Content -->
                        <div v-if="gradingSubmission.content">
                            <h3 class="text-sm font-medium text-gray-500">Contenido</h3>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg text-sm text-gray-700 whitespace-pre-wrap">
                                {{ gradingSubmission.content }}
                            </div>
                        </div>

                        <!-- Grade Input -->
                        <div>
                            <InputLabel for="grade" :value="`Calificación (0 - ${task?.max_score})`" />
                            <TextInput
                                id="grade"
                                v-model.number="gradeValue"
                                type="number"
                                :min="0"
                                :max="task?.max_score"
                                step="0.5"
                                class="mt-1 block w-full text-lg"
                                placeholder="0"
                            />
                        </div>

                        <!-- Feedback -->
                        <div>
                            <InputLabel for="feedback" value="Retroalimentación (opcional)" />
                            <textarea
                                id="feedback"
                                v-model="feedback"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                                placeholder="Comentarios para el estudiante..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <SecondaryButton @click="closeGrading" :disabled="saving">Cancelar</SecondaryButton>
                        <PrimaryButton @click="submitGrade" :disabled="saving || gradeValue === null">
                            {{ saving ? 'Guardando...' : 'Guardar Calificación' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
