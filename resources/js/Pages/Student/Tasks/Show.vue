<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useAuth } from '@/composables/useAuth';
import type { Task, TaskSubmission } from '@/types';

const props = defineProps<{
    taskId: number;
}>();

const { user } = useAuth();

const task = ref<Task | null>(null);
const submission = ref<TaskSubmission | null>(null);
const loading = ref(true);
const submitting = ref(false);
const error = ref('');
const success = ref('');

// Form data
const content = ref('');
const files = ref<File[]>([]);
const fileInput = ref<HTMLInputElement | null>(null);

const fetchTask = async () => {
    loading.value = true;
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
        
        await fetchSubmission();
    } catch (err) {
        console.error('Error fetching task:', err);
        error.value = 'Error al cargar la tarea';
    } finally {
        loading.value = false;
    }
};

const fetchSubmission = async () => {
    try {
        const response = await fetch(`/api/v1/task-submissions?task_id=${props.taskId}&student_id=${user.value.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        if (data.data && data.data.length > 0) {
            submission.value = data.data[0];
            content.value = submission.value?.content || '';
        }
    } catch (err) {
        console.error('Error fetching submission:', err);
    }
};

const formatDate = (date: string) => {
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

const canSubmit = computed(() => {
    if (!task.value) return false;
    if (submission.value?.status === 'graded') return false;
    return true;
});

const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        files.value = [...files.value, ...Array.from(input.files)];
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const removeFile = (index: number) => {
    files.value.splice(index, 1);
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getFileIcon = (fileName: string) => {
    const ext = fileName.split('.').pop()?.toLowerCase();
    if (['pdf'].includes(ext || '')) return '📄';
    if (['doc', 'docx'].includes(ext || '')) return '📝';
    if (['xls', 'xlsx'].includes(ext || '')) return '📊';
    if (['ppt', 'pptx'].includes(ext || '')) return '📽️';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext || '')) return '🖼️';
    if (['zip', 'rar', '7z'].includes(ext || '')) return '📦';
    return '📎';
};

const submitTask = async () => {
    if (!content.value.trim() && files.value.length === 0) {
        error.value = 'Debes agregar contenido o archivos para entregar la tarea';
        return;
    }

    submitting.value = true;
    error.value = '';
    success.value = '';

    try {
        const formData = new FormData();
        formData.append('task_id', props.taskId.toString());
        formData.append('student_id', user.value.id.toString());
        formData.append('content', content.value);
        
        files.value.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        const response = await axios.post('/api/v1/task-submissions', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const data = response.data;

        if (!data.success) {
            throw new Error(data.message || 'Error al enviar la tarea');
        }

        success.value = '¡Tarea entregada exitosamente!';
        submission.value = data.data;
        files.value = [];
    } catch (err: any) {
        error.value = err.response?.data?.message || err.message || 'Error al enviar la tarea';
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchTask();
});
</script>

<template>
    <Head :title="task?.title || 'Detalle de Tarea'" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('student.tasks')" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a Mis Tareas
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">{{ task?.title || 'Cargando...' }}</h1>
                </div>
                <div class="flex items-center space-x-2">
                    <Badge v-if="submission" :color="getStatusColor(submission.status)" size="lg">
                        {{ getStatusLabel(submission.status) }}
                    </Badge>
                    <Badge v-else color="gray" size="lg">Pendiente</Badge>
                </div>
            </div>
        </template>

        <!-- Loading -->
        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando tarea...</span>
            </div>
        </Card>

        <template v-else-if="task">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Content - Task Details & Submission Form -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Task Info Card -->
                    <Card>
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <Badge :color="getTaskTypeColor(task.type)" size="lg">
                                {{ getTaskTypeLabel(task.type) }}
                            </Badge>
                            <span class="text-gray-500">{{ task.subject_assignment?.subject?.name }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-500">Prof. {{ task.subject_assignment?.teacher?.name }}</span>
                        </div>

                        <!-- Description -->
                        <div v-if="task.description" class="mb-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Descripción</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ task.description }}</p>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div v-if="task.instructions">
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

                        <!-- Graded Result -->
                        <div v-if="submission?.status === 'graded'" class="bg-green-50 border border-green-200 rounded-lg p-6">
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
                    </Card>

                    <!-- Submission Form -->
                    <Card v-if="canSubmit">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ submission ? 'Actualizar Entrega' : 'Entregar Tarea' }}
                        </h3>

                        <!-- Messages -->
                        <div v-if="error" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start">
                            <svg class="w-4 h-4 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-red-600">{{ error }}</p>
                        </div>
                        <div v-if="success" class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-green-600">{{ success }}</p>
                        </div>

                        <!-- Content Input -->
                        <div class="mb-5">
                            <InputLabel value="Tu respuesta" class="mb-2 text-sm" />
                            <textarea
                                v-model="content"
                                rows="4"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 resize-none text-sm"
                                placeholder="Escribe aquí tu respuesta o comentarios..."
                            ></textarea>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-5">
                            <InputLabel value="Archivos adjuntos (opcional)" class="mb-2 text-sm" />
                            <div 
                                class="border-2 border-dashed border-gray-300 rounded-lg p-5 text-center hover:border-blue-500 hover:bg-blue-50/50 transition-all cursor-pointer"
                                @click="fileInput?.click()"
                                @dragover.prevent
                                @drop.prevent="(e) => { if (e.dataTransfer?.files) files = [...files, ...Array.from(e.dataTransfer.files)] }"
                            >
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-1 text-sm text-gray-600">
                                    <span class="font-medium text-blue-600">Subir archivos</span> o arrastrar
                                </p>
                                <p class="text-xs text-gray-400">
                                    PDF, DOC, DOCX, XLS, JPG, PNG, ZIP (máx. 10MB)
                                </p>
                            </div>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                class="hidden"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.rar"
                                @change="handleFileSelect"
                            />

                            <!-- File List -->
                            <div v-if="files.length > 0" class="mt-3 space-y-1">
                                <div 
                                    v-for="(file, index) in files" 
                                    :key="index"
                                    class="flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200"
                                >
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg">{{ getFileIcon(file.name) }}</span>
                                        <div>
                                            <p class="text-xs font-medium text-gray-900 truncate max-w-[200px]">{{ file.name }}</p>
                                            <p class="text-xs text-gray-400">{{ formatFileSize(file.size) }}</p>
                                        </div>
                                    </div>
                                    <button 
                                        @click="removeFile(index)"
                                        class="p-1 text-gray-400 hover:text-red-500 rounded"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-2 pt-3 border-t border-gray-100">
                            <Link :href="route('student.tasks')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                            <PrimaryButton @click="submitTask" :disabled="submitting">
                                <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                {{ submitting ? 'Enviando...' : 'Entregar Tarea' }}
                            </PrimaryButton>
                        </div>
                    </Card>

                    <!-- Already Submitted View (not graded) -->
                    <Card v-if="submission && submission.status !== 'graded' && !canSubmit">
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">Tarea Entregada</h3>
                            <p class="mt-1 text-gray-500">Tu entrega está pendiente de calificación.</p>
                        </div>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Points Card -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white rounded-lg shadow-lg p-5 text-center">
                        <p class="text-xs uppercase tracking-wide opacity-90">Puntos máximos</p>
                        <p class="text-3xl font-bold mt-1">{{ task.max_score }}</p>
                    </div>

                    <!-- Due Date Card -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Fecha de entrega
                        </h4>
                        <div v-if="task.due_date" :class="['p-3 rounded-lg text-center', isOverdue ? 'bg-red-50' : 'bg-gray-50']">
                            <p :class="['text-base font-bold', isOverdue ? 'text-red-600' : 'text-gray-900']">
                                {{ formatDate(task.due_date) }}
                            </p>
                            <p v-if="isOverdue" class="text-xs text-red-500 mt-1">⚠️ Fecha vencida</p>
                        </div>
                        <p v-else class="text-gray-500 text-sm text-center py-2">Sin fecha límite</p>
                    </div>

                    <!-- Status Card -->
                    <div v-if="submission" class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Tu entrega
                        </h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Estado</span>
                                <Badge :color="getStatusColor(submission.status)" size="sm">
                                    {{ getStatusLabel(submission.status) }}
                                </Badge>
                            </div>
                            <div v-if="submission.score !== null && submission.score !== undefined" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Nota</span>
                                <span class="text-base font-bold text-green-600">{{ submission.score }} / {{ task.max_score }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Información</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Tipo</span>
                                <Badge :color="getTaskTypeColor(task.type)" size="sm">
                                    {{ getTaskTypeLabel(task.type) }}
                                </Badge>
                            </div>
                            <div v-if="task.term" class="flex items-center justify-between">
                                <span class="text-gray-500">Lapso</span>
                                <span class="text-gray-900">{{ task.term.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
