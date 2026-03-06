<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Alert from '@/Components/UI/Alert.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';

interface Reenrollment {
    id: number;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    student_notes: string | null;
    admin_notes: string | null;
    processed_at: string | null;
    created_at: string;
    target_academic_period: {
        id: number;
        name: string;
        school_year: string;
    };
    target_grade: {
        id: number;
        name: string;
    };
    target_section?: {
        id: number;
        name: string;
    };
    processed_by?: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    reenrollments: Reenrollment[];
}>();

const cancelForm = useForm({});

const cancelReenrollment = (reenrollment: Reenrollment) => {
    if (!confirm('¿Estás seguro de que deseas cancelar esta solicitud?')) return;
    
    cancelForm.post(route('student.reenrollment.cancel', reenrollment.id), {
        preserveScroll: true,
    });
};

const getStatusConfig = (status: string) => {
    const configs = {
        pending: { color: 'yellow', label: 'Pendiente' },
        approved: { color: 'green', label: 'Aprobada' },
        rejected: { color: 'red', label: 'Rechazada' },
        cancelled: { color: 'gray', label: 'Cancelada' },
    };
    return configs[status as keyof typeof configs] || configs.pending;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const hasPending = computed(() => {
    return props.reenrollments.some(r => r.status === 'pending');
});
</script>

<template>
    <AppLayout>
        <Head title="Estado de Reinscripción" />

        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Mis Solicitudes de Reinscripción</h1>
                        <p class="text-gray-600 mt-1">Historial de tus solicitudes de reinscripción.</p>
                    </div>
                    <Link
                        v-if="!hasPending"
                        :href="route('student.reenrollment.create')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <BookOpen class="w-4 h-4 mr-2" />
                        Nueva Solicitud
                    </Link>
                </div>

                <!-- Alert if pending exists -->
                <Alert v-if="hasPending" type="info" class="mb-6">
                    Tienes una solicitud de reinscripción pendiente. No puedes crear una nueva solicitud hasta que se procese la actual.
                </Alert>

                <!-- Empty State -->
                <EmptyState
                    v-if="reenrollments.length === 0"
                    title="No tienes solicitudes"
                    description="Aún no has realizado ninguna solicitud de reinscripción."
                    icon="BookOpen"
                >
                    <Link
                        :href="route('student.reenrollment.create')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Solicitar Reinscripción
                    </Link>
                </EmptyState>

                <!-- Reenrollments List -->
                <div v-else class="space-y-4">
                    <Card
                        v-for="reenrollment in reenrollments"
                        :key="reenrollment.id"
                        class="relative"
                    >
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            <Badge :color="getStatusConfig(reenrollment.status).color" size="lg">
                                {{ getStatusConfig(reenrollment.status).label }}
                            </Badge>
                        </div>

                        <div class="p-6">
                            <!-- Header Info -->
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Solicitud #{{ reenrollment.id }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Enviada el {{ formatDate(reenrollment.created_at) }}
                                </p>
                            </div>

                            <!-- Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <label class="text-xs font-medium text-gray-500 uppercase">Período Destino</label>
                                    <p class="text-gray-900 font-medium">{{ reenrollment.target_academic_period.name }}</p>
                                    <p class="text-sm text-gray-500">{{ reenrollment.target_academic_period.school_year }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <label class="text-xs font-medium text-gray-500 uppercase">Grado</label>
                                    <p class="text-gray-900 font-medium">{{ reenrollment.target_grade.name }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <label class="text-xs font-medium text-gray-500 uppercase">Sección</label>
                                    <p class="text-gray-900 font-medium">
                                        {{ reenrollment.target_section?.name || 'Por asignar' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Student Notes -->
                            <div v-if="reenrollment.student_notes" class="mb-4">
                                <label class="text-sm font-medium text-gray-700">Tus notas:</label>
                                <p class="text-gray-600 text-sm mt-1 bg-gray-50 p-3 rounded-lg">{{ reenrollment.student_notes }}</p>
                            </div>

                            <!-- Admin Notes (only for processed requests) -->
                            <div v-if="reenrollment.admin_notes && reenrollment.status !== 'pending'" class="mb-4">
                                <label class="text-sm font-medium text-gray-700">Respuesta de administración:</label>
                                <p class="text-gray-600 text-sm mt-1 bg-blue-50 p-3 rounded-lg">{{ reenrollment.admin_notes }}</p>
                            </div>

                            <!-- Processed Info -->
                            <div v-if="reenrollment.processed_at" class="text-sm text-gray-500 mb-4">
                                Procesada el {{ formatDate(reenrollment.processed_at) }}
                                <span v-if="reenrollment.processed_by">por {{ reenrollment.processed_by.name }}</span>
                            </div>

                            <!-- Actions -->
                            <div v-if="reenrollment.status === 'pending'" class="flex justify-end">
                                <Button
                                    variant="danger"
                                    size="sm"
                                    :disabled="cancelForm.processing"
                                    @click="cancelReenrollment(reenrollment)"
                                >
                                    Cancelar Solicitud
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
