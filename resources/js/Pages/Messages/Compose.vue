<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';
import type { User } from '@/types';

const { get, post } = useApi();
const recipients = ref<User[]>([]);
const loading = ref(false);
const sent = ref(false);

const form = ref({
    recipient_id: '',
    subject: '',
    body: '',
});

const errors = ref<Record<string, string>>({});

const fetchRecipients = async () => {
    const response = await get<User[]>('/api/v1/messages/recipients');
    recipients.value = response ?? [];
};

const submit = async () => {
    loading.value = true;
    errors.value = {};

    const response = await post('/api/v1/messages', form.value);

    if (response) {
        sent.value = true;
        form.value = { recipient_id: '', subject: '', body: '' };
    } else {
        errors.value = { general: 'No se pudo enviar el mensaje. Verifica los datos.' };
    }

    loading.value = false;
};

onMounted(fetchRecipients);
</script>

<template>
    <Head title="Nuevo mensaje" />
    <AppLayout>
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Nuevo mensaje</h1>

            <div v-if="sent" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">
                Mensaje enviado correctamente.
            </div>

            <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                <div>
                    <label for="recipient" class="block text-sm font-medium text-gray-700">Destinatario</label>
                    <select id="recipient" v-model="form.recipient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="" disabled>Selecciona un destinatario</option>
                        <option v-for="user in recipients" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                    </select>
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Asunto</label>
                    <input id="subject" v-model="form.subject" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>

                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700">Mensaje</label>
                    <textarea id="body" v-model="form.body" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                </div>

                <div v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</div>

                <div class="flex justify-end gap-3">
                    <a :href="route('messages.index')" class="rounded-md px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                    <button type="submit" :disabled="loading" class="rounded-md bg-sabere-accent px-4 py-2 text-sm font-semibold text-white hover:bg-sabere-accent/90 disabled:opacity-50">
                        {{ loading ? 'Enviando...' : 'Enviar' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
