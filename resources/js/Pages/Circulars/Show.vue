<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

defineProps<{ circular: any }>();

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
    <AppLayout title="Circular">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('circulars.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-gray-900">{{ circular.title }}</h1>
                        <Badge :color="priorityColor(circular.priority) as any">{{ priorityLabel(circular.priority) }}</Badge>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ circular.creator?.name }} &middot; {{ new Date(circular.sent_at).toLocaleDateString() }}
                    </p>
                </div>
            </div>

            <Card>
                <div class="prose max-w-none text-gray-700" v-html="circular.content" />
            </Card>
        </div>
    </AppLayout>
</template>
