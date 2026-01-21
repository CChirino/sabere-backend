<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useAuth } from '@/composables/useAuth';
import type { SubjectAssignment, Term } from '@/types';

const { user } = useAuth();
const assignments = ref<SubjectAssignment[]>([]);
const terms = ref<Term[]>([]);
const loading = ref(true);

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

const fetchAssignments = async () => {
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

watch(() => form.subject_assignment_id, async (newVal) => {
    if (newVal) {
        const assignment = assignments.value.find(a => a.id === parseInt(newVal));
        if (assignment) {
            await fetchTerms(assignment.academic_period_id);
            form.term_id = '';
        }
    } else {
        terms.value = [];
        form.term_id = '';
    }
});

const submit = () => {
    form.post(route('teacher.tasks.store'));
};

onMounted(async () => {
    await fetchAssignments();
    loading.value = false;
});
</script>

<template>
    <Head title="Nueva Tarea" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Nueva Tarea</h1>
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

        <div v-else-if="assignments.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin asignaciones</h3>
            <p class="mt-1 text-gray-500">No tienes materias asignadas para crear tareas.</p>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-6">
            <!-- Basic Info -->
            <Card>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Información Básica</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="subject_assignment_id" value="Materia y Sección" />
                        <select 
                            id="subject_assignment_id" 
                            v-model="form.subject_assignment_id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                            required
                        >
                            <option value="">Seleccionar materia</option>
                            <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                {{ assignment.subject?.name }} - {{ assignment.section?.grade?.name }} {{ assignment.section?.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subject_assignment_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="term_id" value="Lapso" />
                        <select 
                            id="term_id" 
                            v-model="form.term_id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                            :disabled="!form.subject_assignment_id"
                            required
                        >
                            <option value="">Seleccionar lapso</option>
                            <option v-for="term in terms" :key="term.id" :value="term.id">
                                {{ term.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.term_id" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="title" value="Título de la Tarea" />
                        <TextInput 
                            id="title" 
                            v-model="form.title" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="Ej: Ejercicios de Matemáticas - Capítulo 3"
                            required
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="type" value="Tipo de Actividad" />
                        <select 
                            id="type" 
                            v-model="form.type" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                            required
                        >
                            <option v-for="type in taskTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="max_score" value="Puntuación Máxima" />
                            <TextInput 
                                id="max_score" 
                                v-model="form.max_score" 
                                type="number" 
                                min="1"
                                max="100"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.max_score" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="weight" value="Peso (%)" />
                            <TextInput 
                                id="weight" 
                                v-model="form.weight" 
                                type="number" 
                                min="0"
                                max="100"
                                class="mt-1 block w-full"
                                placeholder="0"
                            />
                            <InputError :message="form.errors.weight" class="mt-2" />
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Description -->
            <Card>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Descripción e Instrucciones</h3>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel for="description" value="Descripción" />
                        <textarea 
                            id="description" 
                            v-model="form.description" 
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                            placeholder="Breve descripción de la tarea..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="instructions" value="Instrucciones para el estudiante" />
                        <textarea 
                            id="instructions" 
                            v-model="form.instructions" 
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                            placeholder="Instrucciones detalladas sobre cómo completar la tarea..."
                        ></textarea>
                        <InputError :message="form.errors.instructions" class="mt-2" />
                    </div>
                </div>
            </Card>

            <!-- Dates -->
            <Card>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Fechas y Publicación</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="available_from" value="Disponible desde (opcional)" />
                        <TextInput 
                            id="available_from" 
                            v-model="form.available_from" 
                            type="datetime-local" 
                            class="mt-1 block w-full"
                        />
                        <p class="mt-1 text-sm text-gray-500">Si no se especifica, estará disponible inmediatamente al publicar.</p>
                        <InputError :message="form.errors.available_from" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="due_date" value="Fecha de entrega" />
                        <TextInput 
                            id="due_date" 
                            v-model="form.due_date" 
                            type="datetime-local" 
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.due_date" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="form.is_published" />
                            <span class="ml-2 text-sm text-gray-700">
                                <strong>Publicar inmediatamente</strong> - Los estudiantes podrán ver esta tarea
                            </span>
                        </label>
                        <InputError :message="form.errors.is_published" class="mt-2" />
                    </div>
                </div>
            </Card>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <Link :href="route('teacher.tasks.index')">
                    <SecondaryButton type="button">Cancelar</SecondaryButton>
                </Link>
                <PrimaryButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Crear Tarea' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
