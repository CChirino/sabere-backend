<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { computed } from 'vue';

const props = defineProps<{
    syllabi: any;
    assignments: any[];
    filters: { assignment_id: string | null };
}>();

const filteredAssignment = computed(() => props.filters.assignment_id || '');

const filterByAssignment = (value: string) => {
    router.get(route('teacher.syllabi.index'), { assignment_id: value || undefined }, {
        preserveState: true,
        preserveScroll: true,
    });
};

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
</script>

<template>
    <AppLayout title="Cronogramas">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Cronogramas</h1>
                    <p class="mt-1 text-sm text-gray-500">Gestiona las planificaciones de tus materias</p>
                </div>
                <Link :href="route('teacher.syllabi.create')">
                    <Button>+ Nuevo Cronograma</Button>
                </Link>
            </div>

            <Card :padding="false">
                <div class="border-b border-gray-200 px-6 py-4">
                    <select
                        :value="filteredAssignment"
                        @change="filterByAssignment(($event.target as HTMLSelectElement).value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Todas las materias</option>
                        <option v-for="a in assignments" :key="a.id" :value="a.id">
                            {{ a.subject?.name }} - {{ a.section?.name }}
                        </option>
                    </select>
                </div>

                <div v-if="syllabi.data.length === 0" class="p-6">
                    <EmptyState
                        title="No hay cronogramas"
                        description="Crea tu primer cronograma para empezar a planificar tus clases."
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Materia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="s in syllabi.data" :key="s.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <Link :href="route('teacher.syllabi.show', s.id)" class="font-medium text-blue-600 hover:text-blue-800">
                                        {{ s.title }}
                                    </Link>
                                    <p v-if="s.description" class="mt-1 text-sm text-gray-500 line-clamp-1">{{ s.description }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ s.subject_assignment?.subject?.name }}<br />
                                    <span class="text-gray-400">{{ s.subject_assignment?.section?.name }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ contentTypeLabel(s.content_type) }}
                                    <p v-if="s.file_name" class="text-xs text-gray-400">{{ s.file_name }} ({{ formatFileSize(s.file_size) }})</p>
                                </td>
                                <td class="px-6 py-4">
                                    <Badge v-if="s.is_published" color="green">Publicado</Badge>
                                    <Badge v-else color="yellow">Borrador</Badge>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ new Date(s.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-2">
                                    <Link :href="route('teacher.syllabi.edit', s.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
                                    <button
                                        @click="router.post(route('teacher.syllabi.toggle-publish', s.id), {}, { preserveScroll: true })"
                                        class="text-yellow-600 hover:text-yellow-800"
                                    >
                                        {{ s.is_published ? 'Despublicar' : 'Publicar' }}
                                    </button>
                                    <Link v-if="s.file_path" :href="route('teacher.syllabi.download', s.id)" class="text-green-600 hover:text-green-800">Descargar</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="syllabi.data.length > 0" class="border-t border-gray-200 px-6 py-4">
                    <Pagination :links="syllabi.links" :current-page="syllabi.current_page" :last-page="syllabi.last_page" :total="syllabi.total" />
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
