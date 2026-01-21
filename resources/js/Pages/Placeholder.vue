<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';

const props = defineProps<{
    title?: string;
    message?: string;
}>();
</script>

<template>
    <Head :title="title || 'Página en Construcción'" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">{{ title || 'Página en Construcción' }}</h1>
        </template>

        <Card class="text-center py-12">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">{{ message || 'Esta página está en desarrollo' }}</h3>
            <p class="mt-2 text-gray-500">Pronto estará disponible.</p>
            <div class="mt-6">
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Volver al Dashboard
                </Link>
            </div>
        </Card>
    </AppLayout>
</template>
