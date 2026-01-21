<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import type { Task, Term } from '@/types';

const props = defineProps<{
    taskId: number;
}>();

const task = ref<Task | null>(null);
const terms = ref<Term[]>([]);
const loading = ref(true);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleting = ref(false);

const form = useForm({
    subject_assignment_id: '',
    term_id: '',
    title: '',
    description: '',
    instructions: '',
    type: 'homework',
    max_score: 20,
    weight: 0,
    due_date: '',
    available_from: '',
    is_published: false,
});

const taskTypes = [
    { value: 'homework', label: 'Tarea' },
    { value: 'exam', label: 'Examen' },
    { value: 'quiz', label: 'Quiz' },
    { value: 'project', label: 'Proyecto' },
    { value: 'activity', label: 'Actividad' },
];

const typeLabels: Record<string, string> = {
    homework: 'Tarea',
    exam: 'Examen',
    quiz: 'Quiz',
    project: 'Proyecto',
    activity: 'Actividad',
};

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
        
        if (task.value) {
            await fetchTerms(task.value.subject_assignment?.academic_period_id);
            populateForm();
        }
    } catch (error) {
        console.error('Error fetching task:', error);
    } finally {
        loading.value = false;
    }
};

const fetchTerms = async (academicPeriodId: number) => {
    try {
        const response = await fetch(`/api/v1/terms?academic_period_id=${academicPeriodId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        terms.value = data.data || [];
    } catch (error) {
        console.error('Error fetching terms:', error);
    }
};

const populateForm = () => {
    if (!task.value) return;
    form.subject_assignment_id = String(task.value.subject_assignment_id);
    form.term_id = String(task.value.term_id);
    form.title = task.value.title;
    form.description = task.value.description || '';
    form.instructions = task.value.instructions || '';
    form.type = task.value.type;
    form.max_score = task.value.max_score;
    form.weight = task.value.weight || 0;
    form.due_date = task.value.due_date ? task.value.due_date.slice(0, 16) : '';
    form.available_from = task.value.available_from ? task.value.available_from.slice(0, 16) : '';
    form.is_published = task.value.is_published;
};

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-VE', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const togglePublish = () => {
    router.post(route('teacher.tasks.toggle-publish', props.taskId), {}, {
        onSuccess: () => fetchTask(),
    });
};

const submitEdit = () => {
    form.put(route('teacher.tasks.update', props.taskId), {
        onSuccess: () => {
            showEditModal.value = false;
            fetchTask();
        },
    });
};

const deleteTask = () => {
    deleting.value = true;
    router.delete(route('teacher.tasks.destroy', props.taskId), {
        onSuccess: () => {
            router.visit(route('teacher.tasks.index'));
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

const submissionsCount = computed(() => task.value?.submissions?.length || 0);

onMounted(() => {
    fetchTask();
});
</script>

<template>
    <Head :title="`Tarea - ${task?.title || 'Cargando...'}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('teacher.tasks.index')" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a Tareas
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">{{ task?.title || 'Cargando...' }}</h1>
                </div>
                <div v-if="task" class="flex items-center space-x-2">
                    <SecondaryButton @click="showEditModal = true">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </SecondaryButton>
                    <PrimaryButton @click="togglePublish">
                        {{ task.is_published ? 'Despublicar' : 'Publicar' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando tarea...</span>
            </div>
        </Card>

        <div v-else-if="task" class="space-y-6">
            <!-- Status and Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="text-center">
                    <div class="text-3xl font-bold text-sabere-primary">{{ task.max_score }}</div>
                    <div class="text-sm text-gray-500">Puntuación Máxima</div>
                </Card>
                <Card class="text-center">
                    <div class="text-3xl font-bold text-sabere-accent">{{ submissionsCount }}</div>
                    <div class="text-sm text-gray-500">Entregas</div>
                    <Link :href="route('teacher.tasks.submissions', task.id)" class="text-sm text-sabere-accent hover:underline mt-2 inline-block">
                        Ver entregas →
                    </Link>
                </Card>
                <Card class="text-center">
                    <Badge :color="task.is_published ? 'green' : 'yellow'" size="lg">
                        {{ task.is_published ? 'Publicada' : 'Borrador' }}
                    </Badge>
                    <div class="text-sm text-gray-500 mt-2">Estado</div>
                </Card>
            </div>

            <!-- Details -->
            <Card>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Materia</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ task.subject_assignment?.subject?.name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Sección</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ task.subject_assignment?.section?.grade?.name }} - {{ task.subject_assignment?.section?.name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Tipo</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ typeLabels[task.type] }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Lapso</h3>
                        <p class="mt-1 text-lg text-gray-900">{{ task.term?.name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Disponible desde</h3>
                        <p class="mt-1 text-gray-900">{{ formatDate(task.available_from) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Fecha de entrega</h3>
                        <p class="mt-1 text-gray-900">{{ formatDate(task.due_date) }}</p>
                    </div>
                </div>
            </Card>

            <!-- Description -->
            <Card v-if="task.description">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Descripción</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ task.description }}</p>
            </Card>

            <!-- Instructions -->
            <Card v-if="task.instructions">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Instrucciones</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ task.instructions }}</p>
            </Card>

            <!-- Danger Zone -->
            <Card class="border-red-200">
                <h3 class="text-lg font-medium text-red-600 mb-2">Zona de Peligro</h3>
                <p class="text-sm text-gray-500 mb-4">Una vez eliminada, la tarea no se puede recuperar.</p>
                <DangerButton @click="showDeleteModal = true" :disabled="submissionsCount > 0">
                    Eliminar Tarea
                </DangerButton>
                <p v-if="submissionsCount > 0" class="text-sm text-red-500 mt-2">
                    No se puede eliminar porque tiene entregas asociadas.
                </p>
            </Card>
        </div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="2xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Editar Tarea</h2>
                
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <InputLabel for="edit-title" value="Título" />
                        <TextInput id="edit-title" v-model="form.title" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="edit-type" value="Tipo" />
                            <select id="edit-type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent">
                                <option v-for="type in taskTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="edit-term" value="Lapso" />
                            <select id="edit-term" v-model="form.term_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent">
                                <option v-for="term in terms" :key="term.id" :value="term.id">{{ term.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="edit-max-score" value="Puntuación Máxima" />
                            <TextInput id="edit-max-score" v-model="form.max_score" type="number" min="1" max="100" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="edit-weight" value="Peso (%)" />
                            <TextInput id="edit-weight" v-model="form.weight" type="number" min="0" max="100" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="edit-description" value="Descripción" />
                        <textarea id="edit-description" v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"></textarea>
                    </div>

                    <div>
                        <InputLabel for="edit-instructions" value="Instrucciones" />
                        <textarea id="edit-instructions" v-model="form.instructions" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="edit-available" value="Disponible desde" />
                            <TextInput id="edit-available" v-model="form.available_from" type="datetime-local" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="edit-due" value="Fecha de entrega" />
                            <TextInput id="edit-due" v-model="form.due_date" type="datetime-local" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <SecondaryButton type="button" @click="showEditModal = false">Cancelar</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Tarea</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ task?.title }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="showDeleteModal = false" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteTask" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
