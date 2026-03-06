<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

interface NotificationHistory {
    type: string;
    title: string;
    total_sent: number;
    last_sent: string;
}

const props = defineProps<{
    notifications: {
        data: NotificationHistory[];
        links: any[];
        meta: any;
    };
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Historial de Notificaciones" />

        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <Link
                            :href="route('admin.notifications.bulk')"
                            class="mr-4 text-gray-500 hover:text-gray-700"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Historial de Notificaciones</h1>
                            <p class="text-gray-600 mt-1">Registro de notificaciones masivas enviadas.</p>
                        </div>
                    </div>
                    <Link
                        :href="route('admin.notifications.bulk')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Nueva Notificación
                    </Link>
                </div>

                <!-- Notifications List -->
                <Card>
                    <div class="p-6">
                        <div v-if="notifications.data.length === 0" class="text-center py-12">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay notificaciones</h3>
                            <p class="text-gray-500 mb-4">Aún no se han enviado notificaciones masivas.</p>
                            <Link
                                :href="route('admin.notifications.bulk')"
                                class="text-blue-600 hover:text-blue-800 font-medium"
                            >
                                Enviar primera notificación →
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="notification in notifications.data"
                                :key="notification.title"
                                class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Badge variant="info">Masiva</Badge>
                                            <h3 class="font-semibold text-gray-900">{{ notification.title }}</h3>
                                        </div>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                {{ notification.total_sent }} destinatarios
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ formatDate(notification.last_sent) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="notifications.links.length > 3" class="mt-6">
                                <div class="flex justify-center gap-1">
                                    <Link
                                        v-for="link in notifications.links"
                                        :key="link.label"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-2 rounded text-sm',
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        ]"
                                        v-html="link.label"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
