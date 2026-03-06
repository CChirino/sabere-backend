<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Modal from '@/Components/UI/Modal.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';

interface Reenrollment {
    id: number;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    student_notes: string | null;
    admin_notes: string | null;
    processed_at: string | null;
    created_at: string;
    student: {
        id: number;
        name: string;
        email: string;
    };
    current_enrollment: {
        section: {
            name: string;
            grade: {
                name: string;
            };
        };
    };
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

interface AcademicPeriod {
    id: number;
    name: string;
}

interface PaginationData {
    data: Reenrollment[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    reenrollments: PaginationData;
    academicPeriods: AcademicPeriod[];
    filters: {
        status?: string;
        academic_period_id?: string;
    };
}>();

const searchQuery = ref('');
const selectedStatus = ref(props.filters.status || 'all');
const selectedPeriod = ref(props.filters.academic_period_id || '');

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedReenrollment = ref<Reenrollment | null>(null);
const adminNotes = ref('');

const approveForm = useForm({
    admin_notes: '',
});

const rejectForm = useForm({
    admin_notes: '',
});

const openApproveModal = (reenrollment: Reenrollment) => {
    selectedReenrollment.value = reenrollment;
    adminNotes.value = '';
    showApproveModal.value = true;
};

const openRejectModal = (reenrollment: Reenrollment) => {
    selectedReenrollment.value = reenrollment;
    adminNotes.value = '';
    showRejectModal.value = true;
};

const approve = () => {
    if (!selectedReenrollment.value) return;
    
    approveForm.admin_notes = adminNotes.value;
    approveForm.post(route('admin.reenrollments.approve', selectedReenrollment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApproveModal.value = false;
            selectedReenrollment.value = null;
            approveForm.reset();
        },
    });
};

const reject = () => {
    if (!selectedReenrollment.value) return;
    
    rejectForm.admin_notes = adminNotes.value;
    rejectForm.post(route('admin.reenrollments.reject', selectedReenrollment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            selectedReenrollment.value = null;
            rejectForm.reset();
        },
    });
};

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (selectedStatus.value && selectedStatus.value !== 'all') {
        params.status = selectedStatus.value;
    }
    if (selectedPeriod.value) {
        params.academic_period_id = selectedPeriod.value;
    }
    
    router.get(route('admin.reenrollments.index'), params, {
        preserveState: true,
        replace: true,
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
        month: 'short',
        year: 'numeric',
    });
};

const filteredReenrollments = computed(() => {
    let items = props.reenrollments.data;
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        items = items.filter(r => 
            r.student.name.toLowerCase().includes(query) ||
            r.student.email.toLowerCase().includes(query) ||
            r.target_grade.name.toLowerCase().includes(query)
        );
    }
    
    return items;
});

const stats = computed(() => {
    const all = props.reenrollments.data;
    return {
        pending: all.filter(r => r.status === 'pending').length,
        approved: all.filter(r => r.status === 'approved').length,
        rejected: all.filter(r => r.status === 'rejected').length,
    };
});
</script>

<template>
    <AppLayout>
        <Head title="Gestión de Reinscripciones" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gestión de Reinscripciones</h1>
                        <p class="text-gray-600 mt-1">Administra las solicitudes de reinscripción de los estudiantes.</p>
                    </div>
                    <Link
                        :href="route('admin.reenrollments.statistics')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Ver Estadísticas
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <Card class="bg-yellow-50 border-yellow-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm text-yellow-600 font-medium">Pendientes</p>
                                <p class="text-2xl font-bold text-yellow-700">{{ stats.pending }}</p>
                            </div>
                        </div>
                    </Card>
                    <Card class="bg-green-50 border-green-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm text-green-600 font-medium">Aprobadas</p>
                                <p class="text-2xl font-bold text-green-700">{{ stats.approved }}</p>
                            </div>
                        </div>
                    </Card>
                    <Card class="bg-red-50 border-red-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm text-red-600 font-medium">Rechazadas</p>
                                <p class="text-2xl font-bold text-red-700">{{ stats.rejected }}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Filters -->
                <Card class="mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <div class="relative">
                                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por nombre, email o grado..."
                                    class="pl-10"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <Select
                                v-model="selectedStatus"
                                :options="[
                                    { value: 'all', label: 'Todos' },
                                    { value: 'pending', label: 'Pendientes' },
                                    { value: 'approved', label: 'Aprobadas' },
                                    { value: 'rejected', label: 'Rechazadas' },
                                    { value: 'cancelled', label: 'Canceladas' },
                                ]"
                                @change="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                            <Select
                                v-model="selectedPeriod"
                                :options="[
                                    { value: '', label: 'Todos' },
                                    ...academicPeriods.map(p => ({ value: String(p.id), label: p.name }))
                                ]"
                                @change="applyFilters"
                            />
                        </div>
                    </div>
                </Card>

                <!-- Empty State -->
                <EmptyState
                    v-if="filteredReenrollments.length === 0"
                    title="No hay solicitudes"
                    description="No se encontraron solicitudes de reinscripción con los filtros aplicados."
                    icon="Inbox"
                />

                <!-- Reenrollments Table -->
                <Card v-else>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estudiante</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destino</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="reenrollment in filteredReenrollments" :key="reenrollment.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ reenrollment.student.name }}</div>
                                                <div class="text-sm text-gray-500">{{ reenrollment.student.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ reenrollment.current_enrollment.section.grade.name }}</div>
                                        <div class="text-sm text-gray-500">{{ reenrollment.current_enrollment.section.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ reenrollment.target_grade.name }}</div>
                                        <div class="text-sm text-gray-500">{{ reenrollment.target_section?.name || 'Por asignar' }}</div>
                                        <div class="text-xs text-blue-600">{{ reenrollment.target_academic_period.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :color="getStatusConfig(reenrollment.status).color">
                                            {{ getStatusConfig(reenrollment.status).label }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(reenrollment.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div v-if="reenrollment.status === 'pending'" class="flex justify-end gap-2">
                                            <Button
                                                variant="success"
                                                size="sm"
                                                @click="openApproveModal(reenrollment)"
                                                :disabled="approveForm.processing"
                                            >
                                                Aprobar
                                            </Button>
                                            <Button
                                                variant="danger"
                                                size="sm"
                                                @click="openRejectModal(reenrollment)"
                                                :disabled="rejectForm.processing"
                                            >
                                                Rechazar
                                            </Button>
                                        </div>
                                        <span v-else class="text-gray-400">
                                            {{ reenrollment.processed_by ? `Por ${reenrollment.processed_by.name}` : 'Procesada' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <Pagination
                        :links="reenrollments.links"
                        :current-page="reenrollments.current_page"
                        :last-page="reenrollments.last_page"
                        :total="reenrollments.total"
                        class="mt-4"
                    />
                </Card>
            </div>
        </div>

        <!-- Approve Modal -->
        <Modal :show="showApproveModal" @close="showApproveModal = false">
            <template #title>Aprobar Reinscripción</template>
            <template #content>
                <div v-if="selectedReenrollment" class="space-y-4">
                    <p class="text-gray-600">
                        Estás aprobando la reinscripción de <strong>{{ selectedReenrollment.student.name }}</strong>
                        al grado <strong>{{ selectedReenrollment.target_grade.name }}</strong>.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas para el estudiante (opcional)</label>
                        <textarea
                            v-model="adminNotes"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Ej: Bienvenido al nuevo período académico..."
                        ></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <Button variant="secondary" @click="showApproveModal = false">Cancelar</Button>
                <Button
                    variant="primary"
                    @click="approve"
                    :disabled="approveForm.processing"
                >
                    {{ approveForm.processing ? 'Aprobando...' : 'Aprobar Reinscripción' }}
                </Button>
            </template>
        </Modal>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" @close="showRejectModal = false">
            <template #title>Rechazar Reinscripción</template>
            <template #content>
                <div v-if="selectedReenrollment" class="space-y-4">
                    <p class="text-gray-600">
                        Estás rechazando la reinscripción de <strong>{{ selectedReenrollment.student.name }}</strong>.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo del rechazo <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="adminNotes"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Explica el motivo del rechazo..."
                            required
                        ></textarea>
                        <p v-if="rejectForm.errors.admin_notes" class="text-red-500 text-sm mt-1">{{ rejectForm.errors.admin_notes }}</p>
                    </div>
                </div>
            </template>
            <template #footer>
                <Button variant="secondary" @click="showRejectModal = false">Cancelar</Button>
                <Button
                    variant="danger"
                    @click="reject"
                    :disabled="rejectForm.processing || !adminNotes"
                >
                    {{ rejectForm.processing ? 'Rechazando...' : 'Rechazar Reinscripción' }}
                </Button>
            </template>
        </Modal>
    </AppLayout>
</template>
