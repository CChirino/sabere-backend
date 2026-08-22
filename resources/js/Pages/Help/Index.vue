<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps<{ categories: any[] }>();

const showSuggestion = ref(false);
const suggestionType = ref('article');

const suggestionForm = useForm({
    type: 'article',
    subject: '',
    description: '',
});

const submitSuggestion = () => {
    suggestionForm.post(route('help.suggestions'), {
        onSuccess: () => {
            showSuggestion.value = false;
            suggestionForm.reset();
        },
    });
};

const iconMap: Record<string, string> = {
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0',
    'book-open': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    'refresh-cw': 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
};

const getIconPath = (icon: string) => iconMap[icon] || iconMap.user;
</script>

<template>
    <AppLayout title="Centro de Ayuda">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h1 class="text-2xl font-bold text-gray-900">Centro de Ayuda</h1><p class="mt-1 text-sm text-gray-500">Encuentra respuestas a tus preguntas</p></div>
                <div class="flex gap-2">
                    <Link :href="route('help.search')" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Buscar
                    </Link>
                    <Button variant="secondary" @click="showSuggestion = true">Sugerir / Preguntar</Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="cat in categories" :key="cat.id">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(cat.icon)" /></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ cat.name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ cat.description }}</p>
                            <div class="mt-3 space-y-1">
                                <Link v-for="art in cat.articles.slice(0, 5)" :key="art.id" :href="route('help.show', art.slug)" class="block text-sm text-blue-600 hover:text-blue-800 hover:underline">{{ art.title }}</Link>
                                <p v-if="cat.articles.length > 5" class="text-xs text-gray-400">+{{ cat.articles.length - 5 }} artículos más</p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <div v-if="showSuggestion" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showSuggestion = false">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">Sugerencia o Pregunta</h2>
                <p class="mt-1 text-sm text-gray-500">¿No encontraste lo que buscabas? Envíanos una sugerencia de artículo o una pregunta.</p>
                <form @submit.prevent="submitSuggestion" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select v-model="suggestionForm.type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm">
                            <option value="article">Sugerir un artículo</option>
                            <option value="question">Hacer una pregunta</option>
                        </select>
                        <p v-if="suggestionForm.errors.type" class="mt-1 text-xs text-red-600">{{ suggestionForm.errors.type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Asunto</label>
                        <input v-model="suggestionForm.subject" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :placeholder="suggestionForm.type === 'article' ? 'Título del artículo sugerido' : 'Tu pregunta'" />
                        <p v-if="suggestionForm.errors.subject" class="mt-1 text-xs text-red-600">{{ suggestionForm.errors.subject }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea v-model="suggestionForm.description" rows="4" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" :placeholder="suggestionForm.type === 'article' ? 'Describe el contenido que te gustaría ver' : 'Explica tu duda con detalle'" />
                        <p v-if="suggestionForm.errors.description" class="mt-1 text-xs text-red-600">{{ suggestionForm.errors.description }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="secondary" @click="showSuggestion = false">Cancelar</Button>
                        <Button type="submit" :disabled="suggestionForm.processing">{{ suggestionForm.processing ? 'Enviando...' : 'Enviar' }}</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
