<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';

interface Stats {
    all_guardians: number;
    all_students: number;
    all_teachers: number;
    all_users: number;
}

const props = defineProps<{
    stats: Stats;
}>();

const form = useForm({
    recipients: [] as string[],
    subject: '',
    message: '',
    send_email: true,
    priority: 'normal' as 'low' | 'normal' | 'high' | 'urgent',
});

const selectedRecipientsLabel = computed(() => {
    if (form.recipients.length === 0) return 'Seleccionar destinatarios';
    if (form.recipients.includes('all_users')) return 'Todos los usuarios';
    
    const labels: Record<string, string> = {
        'all_guardians': 'Todos los representantes',
        'all_students': 'Todos los estudiantes',
        'all_teachers': 'Todos los profesores',
    };
    
    return form.recipients.map(r => labels[r]).join(', ');
});

const estimatedRecipients = computed(() => {
    if (form.recipients.includes('all_users')) return props.stats.all_users;
    
    let count = 0;
    if (form.recipients.includes('all_guardians')) count += props.stats.all_guardians;
    if (form.recipients.includes('all_students')) count += props.stats.all_students;
    if (form.recipients.includes('all_teachers')) count += props.stats.all_teachers;
    
    return count;
});

const priorityOptions = [
    { value: 'low', label: 'Baja', color: 'green' },
    { value: 'normal', label: 'Normal', color: 'blue' },
    { value: 'high', label: 'Alta', color: 'yellow' },
    { value: 'urgent', label: 'Urgente', color: 'red' },
];

const recipientOptions = [
    { value: 'all_guardians', label: 'Todos los representantes', count: props.stats.all_guardians },
    { value: 'all_students', label: 'Todos los estudiantes', count: props.stats.all_students },
    { value: 'all_teachers', label: 'Todos los profesores', count: props.stats.all_teachers },
    { value: 'all_users', label: 'Todos los usuarios', count: props.stats.all_users },
];

const toggleRecipient = (value: string) => {
    if (value === 'all_users') {
        // If selecting all_users, clear other selections
        if (!form.recipients.includes('all_users')) {
            form.recipients = ['all_users'];
        } else {
            form.recipients = form.recipients.filter(r => r !== 'all_users');
        }
    } else {
        // If selecting specific group, remove all_users if present
        form.recipients = form.recipients.filter(r => r !== 'all_users');
        
        if (form.recipients.includes(value)) {
            form.recipients = form.recipients.filter(r => r !== value);
        } else {
            form.recipients.push(value);
        }
    }
};

const submit = () => {
    if (form.recipients.length === 0) {
        alert('Por favor selecciona al menos un grupo de destinatarios');
        return;
    }
    
    if (!confirm(`¿Estás seguro de enviar esta notificación a ${estimatedRecipients.value} destinatarios?`)) {
        return;
    }
    
    form.post(route('admin.notifications.send'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Notificaciones Masivas" />

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-blue-100 rounded-lg mr-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Notificaciones Masivas</h1>
                            <p class="text-gray-600">Envía correos y notificaciones a grupos de usuarios.</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Recipients Selection -->
                    <Card class="mb-6">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Destinatarios
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    v-for="option in recipientOptions"
                                    :key="option.value"
                                    @click="toggleRecipient(option.value)"
                                    :class="[
                                        'cursor-pointer rounded-lg border-2 p-4 transition-all',
                                        form.recipients.includes(option.value)
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                                <span class="text-lg font-bold text-gray-600">{{ option.label.charAt(0) }}</span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ option.label }}</p>
                                                <p class="text-sm text-gray-500">{{ option.count }} usuarios</p>
                                            </div>
                                        </div>
                                        <div
                                            :class="[
                                                'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                                                form.recipients.includes(option.value)
                                                    ? 'border-blue-500 bg-blue-500'
                                                    : 'border-gray-300'
                                            ]"
                                        >
                                            <svg v-if="form.recipients.includes(option.value)" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.errors.recipients" class="mt-2 text-sm text-red-600">
                                {{ form.errors.recipients }}
                            </div>
                        </div>
                    </Card>

                    <!-- Message Content -->
                    <Card class="mb-6">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Contenido del Mensaje
                            </h2>

                            <!-- Subject -->
                            <div class="mb-4">
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Asunto *
                                </label>
                                <input
                                    id="subject"
                                    v-model="form.subject"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Ej: Recordatorio de pago de mensualidad"
                                    required
                                />
                                <div v-if="form.errors.subject" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.subject }}
                                </div>
                            </div>

                            <!-- Priority -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Prioridad
                                </label>
                                <div class="flex gap-2">
                                    <button
                                        v-for="option in priorityOptions"
                                        :key="option.value"
                                        type="button"
                                        @click="form.priority = option.value as any"
                                        :class="[
                                            'flex items-center px-4 py-2 rounded-lg border-2 transition-all',
                                            form.priority === option.value
                                                ? `border-${option.color}-500 bg-${option.color}-50`
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <span :class="form.priority === option.value ? `text-${option.color}-700 font-medium` : 'text-gray-600'">
                                            {{ option.label }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Message -->
                            <div class="mb-4">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Mensaje *
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="8"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Escribe el contenido de la notificación aquí..."
                                    required
                                ></textarea>
                                <div class="flex justify-between mt-1">
                                    <div v-if="form.errors.message" class="text-sm text-red-600">
                                        {{ form.errors.message }}
                                    </div>
                                    <div class="text-sm text-gray-500 ml-auto">
                                        {{ form.message.length }}/5000 caracteres
                                    </div>
                                </div>
                            </div>

                            <!-- Email Option -->
                            <div class="flex items-center">
                                <input
                                    id="send_email"
                                    v-model="form.send_email"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="send_email" class="ml-2 block text-sm text-gray-700">
                                    Enviar también por correo electrónico
                                </label>
                            </div>
                        </div>
                    </Card>

                    <!-- Summary -->
                    <Card class="mb-6" v-if="form.recipients.length > 0">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Resumen de Envío</h2>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-600">Destinatarios seleccionados:</span>
                                    <span class="font-medium text-gray-900">{{ selectedRecipientsLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-600">Total estimado de destinatarios:</span>
                                    <Badge variant="primary" size="lg">{{ estimatedRecipients }} usuarios</Badge>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-600">Canal de envío:</span>
                                    <span class="font-medium text-gray-900">
                                        {{ form.send_email ? 'Notificación + Email' : 'Solo notificación' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Prioridad:</span>
                                    <Badge 
                                        :variant="form.priority === 'urgent' ? 'danger' : form.priority === 'high' ? 'warning' : form.priority === 'low' ? 'success' : 'info'"
                                    >
                                        {{ priorityOptions.find(p => p.value === form.priority)?.label }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between">
                        <a
                            :href="route('admin.notifications.history')"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            Ver historial de notificaciones →
                        </a>
                        <Button
                            type="submit"
                            variant="primary"
                            size="lg"
                            :disabled="form.processing || form.recipients.length === 0 || !form.subject || !form.message"
                            class="flex items-center"
                        >
                            <svg v-if="!form.processing" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span v-if="form.processing">Enviando...</span>
                            <span v-else>Enviar Notificación ({{ estimatedRecipients }})</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
