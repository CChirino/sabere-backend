<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps<{ syllabi: any }>();

const contentTypeLabel = (type: string) => {
    const labels: Record<string, string> = { file: 'Archivo', editor: 'Editor', both: 'Archivo + Editor' };
    return labels[type] || type;
};
</script>

<template>
    <AppLayout title="Cronogramas">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cronogramas</h1>
                <p class="mt-1 text-sm text-gray-500">Cronogramas publicados por tus profesores</p>
            </div>

            <div v-if="syllabi.data.length === 0">
                <Card><EmptyState title="No hay cronogramas" description="Tus profesores aún no han publicado cronogramas." /></Card>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="s in syllabi.data" :key="s.id" :padding="false">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <Link :href="route('student.syllabi.show', s.id)" class="font-semibold text-gray-900 hover:text-blue-600">{{ s.title }}</Link>
                            <Badge color="blue">{{ contentTypeLabel(s.content_type) }}</Badge>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ s.subject_assignment?.subject?.name }} - {{ s.subject_assignment?.section?.name }}</p>
                        <p v-if="s.term" class="mt-1 text-xs text-gray-400">{{ s.term?.name }}</p>
                        <p v-if="s.description" class="mt-2 text-sm text-gray-600 line-clamp-2">{{ s.description }}</p>
                        <div class="mt-3 flex items-center gap-3">
                            <Link v-if="s.file_path" :href="route('student.syllabi.download', s.id)" class="text-sm text-blue-600 hover:text-blue-800">Descargar archivo</Link>
                            <Link :href="route('student.syllabi.show', s.id)" class="text-sm text-blue-600 hover:text-blue-800">Ver detalle</Link>
                        </div>
                    </div>
                </Card>
            </div>

            <Pagination v-if="syllabi.data.length > 0" :links="syllabi.links" :current-page="syllabi.current_page" :last-page="syllabi.last_page" :total="syllabi.total" />
        </div>
    </AppLayout>
</template>
