<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import InputError from '@/Components/InputError.vue';
import { ref, computed } from 'vue';

const props = defineProps<{
    assignments: any[];
}>();

const activeTab = ref<'file' | 'editor'>('file');

const form = useForm({
    subject_assignment_id: '',
    term_id: '',
    title: '',
    description: '',
    content_type: 'file' as 'file' | 'editor' | 'both',
    file: null as File | null,
    content: '',
    objectives: [] as string[],
    topics: [] as { week: string; topic: string; description: string }[],
    evaluation_criteria: [] as string[],
    resources: [] as string[],
});

const selectedAssignment = computed(() => {
    return props.assignments.find((a: any) => a.id === Number(form.subject_assignment_id));
});

const terms = computed(() => {
    return selectedAssignment.value?.academic_period?.terms || [];
});

const addObjective = () => form.objectives.push('');
const removeObjective = (i: number) => form.objectives.splice(i, 1);
const addTopic = () => form.topics.push({ week: '', topic: '', description: '' });
const removeTopic = (i: number) => form.topics.splice(i, 1);
const addCriteria = () => form.evaluation_criteria.push('');
const removeCriteria = (i: number) => form.evaluation_criteria.splice(i, 1);
const addResource = () => form.resources.push('');
const removeResource = (i: number) => form.resources.splice(i, 1);

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) {
        form.file = target.files[0];
    }
};

const updateContentType = () => {
    if (activeTab.value === 'file' && form.content) {
        form.content_type = 'both';
    } else if (activeTab.value === 'editor' && form.file) {
        form.content_type = 'both';
    } else {
        form.content_type = activeTab.value;
    }
};

const submit = () => {
    updateContentType();
    form.post(route('teacher.syllabi.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout title="Crear Cronograma">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('teacher.syllabi.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Crear Cronograma</h1>
                    <p class="mt-1 text-sm text-gray-500">Planifica y organiza el contenido de tu materia</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card title="Información General">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Materia / Asignación *</label>
                            <select v-model="form.subject_assignment_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Seleccionar materia</option>
                                <option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.subject?.name }} - {{ a.section?.name }}</option>
                            </select>
                            <InputError :message="form.errors.subject_assignment_id" class="mt-1" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Lapso</label>
                            <select v-model="form.term_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todo el período</option>
                                <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <InputError :message="form.errors.term_id" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Título *</label>
                            <Input v-model="form.title" placeholder="Ej: Planificación Matemáticas 1er Lapso" required />
                            <InputError :message="form.errors.title" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea v-model="form.description" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Breve descripción del cronograma" />
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>
                    </div>
                </Card>

                <Card title="Contenido del Cronograma">
                    <div class="mb-4">
                        <div class="flex border-b border-gray-200">
                            <button
                                type="button"
                                :class="['px-4 py-2 text-sm font-medium', activeTab === 'file' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700']"
                                @click="activeTab = 'file'"
                            >
                                Subir Archivo
                            </button>
                            <button
                                type="button"
                                :class="['px-4 py-2 text-sm font-medium', activeTab === 'editor' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700']"
                                @click="activeTab = 'editor'"
                            >
                                Crear en Sistema
                            </button>
                        </div>
                    </div>

                    <div v-if="activeTab === 'file'" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Archivo (PDF, DOC, DOCX - máx 10MB)</label>
                            <input type="file" accept=".pdf,.doc,.docx" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100" @change="handleFileChange" />
                            <InputError :message="form.errors.file" class="mt-1" />
                            <p v-if="form.file" class="mt-2 text-sm text-gray-600">Archivo seleccionado: {{ (form.file as File).name }}</p>
                        </div>
                    </div>

                    <div v-if="activeTab === 'editor'" class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700">Objetivos</label>
                                <button type="button" @click="addObjective" class="text-sm text-blue-600 hover:text-blue-800">+ Agregar</button>
                            </div>
                            <div v-for="(_, i) in form.objectives" :key="i" class="flex gap-2 mb-2">
                                <input v-model="form.objectives[i]" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Objetivo del cronograma" />
                                <button type="button" @click="removeObjective(i)" class="text-red-500 hover:text-red-700">&times;</button>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700">Temas por Semana/Unidad</label>
                                <button type="button" @click="addTopic" class="text-sm text-blue-600 hover:text-blue-800">+ Agregar</button>
                            </div>
                            <div v-for="(_, i) in form.topics" :key="i" class="mb-3 rounded-lg border border-gray-200 p-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <input v-model="form.topics[i].week" type="text" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Semana/Unidad" />
                                    <input v-model="form.topics[i].topic" type="text" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Tema" />
                                    <div class="flex gap-2">
                                        <input v-model="form.topics[i].description" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Descripción" />
                                        <button type="button" @click="removeTopic(i)" class="text-red-500 hover:text-red-700">&times;</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700">Criterios de Evaluación</label>
                                <button type="button" @click="addCriteria" class="text-sm text-blue-600 hover:text-blue-800">+ Agregar</button>
                            </div>
                            <div v-for="(_, i) in form.evaluation_criteria" :key="i" class="flex gap-2 mb-2">
                                <input v-model="form.evaluation_criteria[i]" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Criterio de evaluación" />
                                <button type="button" @click="removeCriteria(i)" class="text-red-500 hover:text-red-700">&times;</button>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-700">Recursos Didácticos</label>
                                <button type="button" @click="addResource" class="text-sm text-blue-600 hover:text-blue-800">+ Agregar</button>
                            </div>
                            <div v-for="(_, i) in form.resources" :key="i" class="flex gap-2 mb-2">
                                <input v-model="form.resources[i]" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Recurso didáctico" />
                                <button type="button" @click="removeResource(i)" class="text-red-500 hover:text-red-700">&times;</button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Contenido adicional</label>
                            <textarea v-model="form.content" rows="6" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Escribe aquí el contenido detallado del cronograma..." />
                        </div>
                    </div>
                </Card>

                <div class="flex justify-end gap-3">
                    <Link :href="route('teacher.syllabi.index')">
                        <Button variant="secondary">Cancelar</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Crear Cronograma' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
