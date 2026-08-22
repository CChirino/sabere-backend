<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';

defineProps<{ article: any }>();
</script>

<template>
    <AppLayout :title="article.title">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('help.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <p class="text-sm text-gray-500">{{ article.category?.name }}</p>
                    <h1 class="text-2xl font-bold text-gray-900">{{ article.title }}</h1>
                </div>
            </div>

            <Card>
                <div class="prose max-w-none text-gray-700" v-html="article.content" />
                <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 text-sm text-gray-400">
                    <span>{{ article.views_count }} {{ article.views_count === 1 ? 'visualización' : 'visualizaciones' }}</span>
                    <span>Actualizado: {{ new Date(article.updated_at).toLocaleDateString() }}</span>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
