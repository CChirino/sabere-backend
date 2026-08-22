<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import InputError from '@/Components/InputError.vue';
import { ref, computed } from 'vue';

const props = defineProps<{ syllabus: any; assignments: any[] }>();
const activeTab = ref<'file' | 'editor'>(props.syllabus.content_type === 'editor' ? 'editor' : 'file');

const form = useForm({
    subject_assignment_id: String(props.syllabus.subject_assignment_id),
    term_id: props.syllabus.term_id ? String(props.syllabus.term_id) : '',
    title: props.syllabus.title,
    description: props.syllabus.description || '',
    content_type: props.syllabus.content_type,
    file: null as File | null,
    content: props.syllabus.content || '',
    objectives: props.syllabus.objectives || [] as string[],
    topics: props.syllabus.topics || [] as { week: string; topic: string; description: string }[],
    evaluation_criteria: props.syllabus.evaluation_criteria || [] as string[],
    resources: props.syllabus.resources || [] as string[],
});

const selectedAssignment = computed(() => props.assignments.find((a: any) => a.id === Number(form.subject_assignment_id)));
const terms = computed(() => selectedAssignment.value?.academic_period?.terms || []);

const submit = () => {
    if (activeTab.value === 'file' && form.content) form.content_type = 'both';
    else if (activeTab.value === 'editor' && form.file) form.content_type = 'both';
    else form.content_type = activeTab.value;
    form.put(route('teacher.syllabi.update', props.syllabus.id), { forceFormData: true });
};
</script>

<template>
    <AppLayout title="Editar Cronograma">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('teacher.syllabi.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Editar Cronograma</h1>
            </div>
            <form @submit.prevent="submit" class="space-y-6">
                <Card title="Información General">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Materia *</label>
                            <select v-model="form.subject_assignment_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Seleccionar</option>
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
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Título *</label>
                            <Input v-model="form.title" required />
                            <InputError :message="form.errors.title" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea v-model="form.description" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                    </div>
                </Card>
                <Card title="Contenido">
                    <div class="mb-4 flex border-b border-gray-200">
                        <button type="button" :class="['px-4 py-2 text-sm font-medium', activeTab === 'file' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500']" @click="activeTab = 'file'">Archivo</button>
                        <button type="button" :class="['px-4 py-2 text-sm font-medium', activeTab === 'editor' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500']" @click="activeTab = 'editor'">Editor</button>
                    </div>
                    <div v-if="activeTab === 'file'" class="space-y-4">
                        <p v-if="syllabus.file_name" class="text-sm text-gray-500">Actual: <Link :href="route('teacher.syllabi.download', syllabus.id)" class="text-blue-600">{{ syllabus.file_name }}</Link></p>
                        <input type="file" accept=".pdf,.doc,.docx" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700" @change="(e: Event) => { const t = e.target as HTMLInputElement; if (t.files?.[0]) form.file = t.files[0]; }" />
                        <InputError :message="form.errors.file" class="mt-1" />
                    </div>
                    <div v-if="activeTab === 'editor'" class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-2"><label class="text-sm font-medium text-gray-700">Objetivos</label><button type="button" @click="form.objectives.push('')" class="text-sm text-blue-600">+ Agregar</button></div>
                            <div v-for="(_, i) in form.objectives" :key="i" class="flex gap-2 mb-2"><input v-model="form.objectives[i]" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm" placeholder="Objetivo" /><button type="button" @click="form.objectives.splice(i, 1)" class="text-red-500">&times;</button></div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2"><label class="text-sm font-medium text-gray-700">Temas</label><button type="button" @click="form.topics.push({ week: '', topic: '', description: '' })" class="text-sm text-blue-600">+ Agregar</button></div>
                            <div v-for="(_, i) in form.topics" :key="i" class="mb-3 grid grid-cols-3 gap-3 rounded-lg border p-3">
                                <input v-model="form.topics[i].week" type="text" class="rounded-lg border-gray-300 shadow-sm" placeholder="Semana" />
                                <input v-model="form.topics[i].topic" type="text" class="rounded-lg border-gray-300 shadow-sm" placeholder="Tema" />
                                <div class="flex gap-2"><input v-model="form.topics[i].description" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm" placeholder="Descripción" /><button type="button" @click="form.topics.splice(i, 1)" class="text-red-500">&times;</button></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2"><label class="text-sm font-medium text-gray-700">Criterios de Evaluación</label><button type="button" @click="form.evaluation_criteria.push('')" class="text-sm text-blue-600">+ Agregar</button></div>
                            <div v-for="(_, i) in form.evaluation_criteria" :key="i" class="flex gap-2 mb-2"><input v-model="form.evaluation_criteria[i]" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm" placeholder="Criterio" /><button type="button" @click="form.evaluation_criteria.splice(i, 1)" class="text-red-500">&times;</button></div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2"><label class="text-sm font-medium text-gray-700">Recursos</label><button type="button" @click="form.resources.push('')" class="text-sm text-blue-600">+ Agregar</button></div>
                            <div v-for="(_, i) in form.resources" :key="i" class="flex gap-2 mb-2"><input v-model="form.resources[i]" type="text" class="flex-1 rounded-lg border-gray-300 shadow-sm" placeholder="Recurso" /><button type="button" @click="form.resources.splice(i, 1)" class="text-red-500">&times;</button></div>
                        </div>
                        <div><label class="mb-1 block text-sm font-medium text-gray-700">Contenido adicional</label><textarea v-model="form.content" rows="6" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" /></div>
                    </div>
                </Card>
                <div class="flex justify-end gap-3">
                    <Link :href="route('teacher.syllabi.index')"><Button variant="secondary">Cancelar</Button></Link>
                    <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Guardar' }}</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
