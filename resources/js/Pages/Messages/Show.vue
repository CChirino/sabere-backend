<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';
import type { DirectMessage } from '@/types';

const page = usePage();
const { get } = useApi();
const message = ref<DirectMessage | null>(null);
const loading = ref(true);

const messageId = (page.props as any).messageId as number;

const fetchMessage = async () => {
    loading.value = true;
    message.value = await get<DirectMessage>(`/api/v1/messages/${messageId}`);
    loading.value = false;
};

const formatDate = (date: string | null) => {
    if (! date) return '—';
    return new Date(date).toLocaleString('es-VE');
};

onMounted(fetchMessage);
</script>

<template>
    <Head title="Mensaje" />
    <AppLayout>
        <div class="max-w-3xl mx-auto p-6">
            <div class="mb-4">
                <a :href="route('messages.index')" class="text-sm text-sabere-accent hover:underline">&larr; Volver a mensajes</a>
            </div>

            <div v-if="loading" class="text-center py-10 text-gray-500">Cargando...</div>

            <div v-else-if="message" class="rounded-lg bg-white p-6 shadow">
                <h1 class="mb-2 text-xl font-bold text-gray-900">{{ message.subject || 'Sin asunto' }}</h1>
                <div class="mb-4 flex items-center justify-between text-sm text-gray-500">
                    <span>De: <strong class="text-gray-900">{{ message.sender?.name }}</strong></span>
                    <span>{{ formatDate(message.created_at) }}</span>
                </div>
                <p class="whitespace-pre-wrap text-gray-700">{{ message.body }}</p>

                <div v-if="message.attachment_path" class="mt-6">
                    <a :href="`/storage/${message.attachment_path}`" target="_blank" class="text-sabere-accent hover:underline">
                        Descargar adjunto: {{ message.attachment_name || 'Archivo' }}
                    </a>
                </div>
            </div>

            <div v-else class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                No se encontró el mensaje.
            </div>
        </div>
    </AppLayout>
</template>
