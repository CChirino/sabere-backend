<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';
import type { DirectMessage } from '@/types';

const { get } = useApi();
const messages = ref<DirectMessage[]>([]);
const loading = ref(true);

const fetchMessages = async () => {
    loading.value = true;
    messages.value = await get<DirectMessage[]>('/api/v1/messages') ?? [];
    loading.value = false;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('es-VE');
};

onMounted(fetchMessages);
</script>

<template>
    <Head title="Mensajes" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Mensajes</h1>
                <Link :href="route('messages.create')" class="rounded-md bg-sabere-accent px-4 py-2 text-sm font-semibold text-white hover:bg-sabere-accent/90">
                    Nuevo mensaje
                </Link>
            </div>

            <div v-if="loading" class="text-center py-10 text-gray-500">Cargando...</div>

            <div v-else-if="messages.length === 0" class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                No tienes mensajes.
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="message in messages"
                    :key="message.id"
                    :href="route('messages.show', message.id)"
                    class="block rounded-lg bg-white p-4 shadow hover:bg-gray-50"
                    :class="{ 'bg-sabere-accent/5': !message.read_at }"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">
                                {{ message.subject || 'Sin asunto' }}
                                <span v-if="!message.read_at" class="ml-2 inline-block h-2 w-2 rounded-full bg-sabere-accent" />
                            </p>
                            <p class="text-sm text-gray-600">De: {{ message.sender?.name }}</p>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ message.body }}</p>
                        </div>
                        <span class="whitespace-nowrap text-xs text-gray-400">{{ formatDate(message.created_at) }}</span>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
