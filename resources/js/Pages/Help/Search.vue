<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';

defineProps<{ results: any[]; query: string | null }>();
</script>

<template>
    <AppLayout title="Buscar en Ayuda">
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('help.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Buscar en Ayuda</h1>
            </div>

            <form :action="route('help.search')" method="GET" class="flex gap-2">
                <input name="q" :value="query || ''" placeholder="Escribe al menos 3 caracteres..." class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Buscar</button>
            </form>

            <div v-if="!query" class="text-center text-sm text-gray-500 py-8">Ingresa un término de búsqueda para encontrar artículos.</div>
            <div v-else-if="results.length === 0">
                <Card><EmptyState title="Sin resultados" :description="`No se encontraron artículos para: ${query}`" /></Card>
            </div>
            <div v-else class="space-y-3">
                <p class="text-sm text-gray-500">{{ results.length }} resultado(s) para "{{ query }}"</p>
                <Card v-for="r in results" :key="r.id" :padding="false">
                    <Link :href="route('help.show', r.slug)" class="block p-4 hover:bg-gray-50">
                        <div class="flex items-center gap-2 text-sm text-gray-400"><span>{{ r.category?.name }}</span></div>
                        <h3 class="mt-1 font-medium text-gray-900">{{ r.title }}</h3>
                        <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ r.content }}</p>
                    </Link>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
