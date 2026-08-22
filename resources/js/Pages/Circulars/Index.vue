<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps<{ circulars: any; readIds: number[] }>();

const priorityColor = (p: string) => {
    const map: Record<string, string> = { urgent: 'red', high: 'orange', normal: 'blue', low: 'gray' };
    return map[p] || 'gray';
};

const priorityLabel = (p: string) => {
    const map: Record<string, string> = { urgent: 'Urgente', high: 'Alta', normal: 'Normal', low: 'Baja' };
    return map[p] || p;
};
</script>

<template>
    <AppLayout title="Circulares">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Circulares</h1>
                <p class="mt-1 text-sm text-gray-500">Comunicados oficiales de la institución</p>
            </div>

            <div v-if="circulars.data.length === 0">
                <Card><EmptyState title="Sin circulares" description="No hay circulares publicadas en este momento." /></Card>
            </div>

            <div v-else class="space-y-4">
                <Card v-for="c in circulars.data" :key="c.id" :padding="false">
                    <Link :href="route('circulars.show', c.id)" class="block p-5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ c.title }}</h3>
                                    <Badge :color="priorityColor(c.priority) as any">{{ priorityLabel(c.priority) }}</Badge>
                                    <Badge v-if="!readIds.includes(c.id)" color="purple">Nuevo</Badge>
                                </div>
                                <p class="mt-2 text-sm text-gray-600 line-clamp-2" v-html="c.content" />
                                <div class="mt-2 flex items-center gap-3 text-xs text-gray-400">
                                    <span>{{ new Date(c.sent_at || c.created_at).toLocaleDateString() }}</span>
                                    <span>&middot;</span>
                                    <span>{{ c.creator?.name }}</span>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </Link>
                </Card>
            </div>

            <Pagination v-if="circulars.data.length > 0" :links="circulars.links" :current-page="circulars.current_page" :last-page="circulars.last_page" :total="circulars.total" />
        </div>
    </AppLayout>
</template>
