<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps<{
    syllabus: any;
}>();

const contentTypeLabel = (type: string) => {
    const labels: Record<string, string> = { file: 'Archivo', editor: 'Editor', both: 'Archivo + Editor' };
    return labels[type] || type;
};

const formatFileSize = (bytes: number | null) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};

const togglePublish = () => {
    router.post(route('teacher.syllabi.toggle-publish', props.syllabus.id), {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Cronograma">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('teacher.syllabi.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ syllabus.title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ syllabus.subject_assignment?.subject?.name }} - {{ syllabus.subject_assignment?.section?.name }}
                        <span v-if="syllabus.term"> &middot; {{ syllabus.term?.name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Badge v-if="syllabus.is_published" variant="success">Publicado</Badge>
                    <Badge v-else variant="warning">Borrador</Badge>
                    <Button variant="secondary" @click="togglePublish">
                        {{ syllabus.is_published ? 'Despublicar' : 'Publicar' }}
                    </Button>
                    <Link :href="route('teacher.syllabi.edit', syllabus.id)">
                        <Button variant="secondary">Editar</Button>
                    </Link>
                </div>
            </div>

            <div v-if="syllabus.description" class="rounded-lg bg-blue-50 p-4">
                <p class="text-sm text-blue-800">{{ syllabus.description }}</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-1 space-y-6">
                    <Card title="Detalles">
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-gray-500">Tipo de contenido</dt>
                                <dd class="text-gray-900">{{ contentTypeLabel(syllabus.content_type) }}</dd>
                            </div>
                            <div v-if="syllabus.file_name">
                                <dt class="font-medium text-gray-500">Archivo</dt>
                                <dd>
                                    <Link :href="route('teacher.syllabi.download', syllabus.id)" class="text-blue-600 hover:text-blue-800">
                                        {{ syllabus.file_name }}
                                    </Link>
                                    <span class="text-gray-400"> ({{ formatFileSize(syllabus.file_size) }})</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Creado</dt>
                                <dd class="text-gray-900">{{ new Date(syllabus.created_at).toLocaleDateString() }}</dd>
                            </div>
                            <div v-if="syllabus.published_at">
                                <dt class="font-medium text-gray-500">Publicado</dt>
                                <dd class="text-gray-900">{{ new Date(syllabus.published_at).toLocaleDateString() }}</dd>
                            </div>
                        </dl>
                    </Card>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <Card v-if="syllabus.objectives?.length" title="Objetivos">
                        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700">
                            <li v-for="(obj, i) in syllabus.objectives" :key="i">{{ obj }}</li>
                        </ul>
                    </Card>

                    <Card v-if="syllabus.topics?.length" title="Temas por Semana/Unidad">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Semana</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Tema</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="(t, i) in syllabus.topics" :key="i">
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ t.week }}</td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ t.topic }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ t.description }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card v-if="syllabus.evaluation_criteria?.length" title="Criterios de Evaluación">
                        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700">
                            <li v-for="(c, i) in syllabus.evaluation_criteria" :key="i">{{ c }}</li>
                        </ul>
                    </Card>

                    <Card v-if="syllabus.resources?.length" title="Recursos Didácticos">
                        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700">
                            <li v-for="(r, i) in syllabus.resources" :key="i">{{ r }}</li>
                        </ul>
                    </Card>

                    <Card v-if="syllabus.content" title="Contenido">
                        <div class="prose prose-sm max-w-none text-gray-700" v-html="syllabus.content" />
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
