<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';

interface TaskOverview {
    id: number;
    title: string;
    type: string;
    due_date: string;
    is_published: boolean;
    teacher: { id: number; name: string };
    subject: { id: number; name: string };
    section: { id: number; name: string };
    submissions_count: number;
    pending_count: number;
    graded_count: number;
}

interface Stats {
    total_tasks: number;
    published_tasks: number;
    pending_submissions: number;
    overdue_tasks: number;
}

const tasks = ref<TaskOverview[]>([]);
const stats = ref<Stats>({ total_tasks: 0, published_tasks: 0, pending_submissions: 0, overdue_tasks: 0 });
const loading = ref(true);
const filter = ref('all');
const pagination = ref<{
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
} | null>(null);

const columns = [
    { key: 'title', label: 'Tarea' },
    { key: 'teacher.name', label: 'Profesor' },
    { key: 'subject.name', label: 'Materia', hideOnMobile: true },
    { key: 'section.name', label: 'Sección', hideOnMobile: true },
    { key: 'due_date', label: 'Vencimiento' },
    { key: 'status', label: 'Estado' },
];

const typeLabels: Record<string, string> = {
    homework: 'Tarea',
    exam: 'Examen',
    quiz: 'Quiz',
    project: 'Proyecto',
    activity: 'Actividad',
};

const fetchTasks = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page),
            per_page: '10',
        });
        if (filter.value !== 'all') {
            params.append('filter', filter.value);
        }
        
        const response = await fetch(`/api/v1/coordinator/tasks-overview?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        tasks.value = data.data || [];
        stats.value = data.stats || stats.value;
        pagination.value = data.pagination || null;
    } catch (error) {
        console.error('Error fetching tasks:', error);
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (page: number) => {
    fetchTasks(page);
};

const handleFilterChange = () => {
    fetchTasks(1);
};

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
    });
};

const isOverdue = (date: string) => {
    if (!date) return false;
    return new Date(date) < new Date();
};

onMounted(() => {
    fetchTasks();
});
</script>

<template>
    <Head title="Seguimiento de Tareas" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Seguimiento de Tareas</h1>
                <select
                    v-model="filter"
                    @change="handleFilterChange"
                    class="flex-shrink-0 w-48 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="all">Todas las tareas</option>
                    <option value="pending">Con entregas pendientes</option>
                    <option value="overdue">Vencidas</option>
                    <option value="draft">Borradores</option>
                </select>
            </div>
        </template>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-blue-600">{{ stats.total_tasks }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Total Tareas</div>
                </div>
            </Card>
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-green-600">{{ stats.published_tasks }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Publicadas</div>
                </div>
            </Card>
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-yellow-600">{{ stats.pending_submissions }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Pendientes</div>
                </div>
            </Card>
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-red-600">{{ stats.overdue_tasks }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Vencidas</div>
                </div>
            </Card>
        </div>

        <Card>
            <DataTable 
                :columns="columns" 
                :data="tasks" 
                :loading="loading"
                :pagination="pagination"
                empty-message="No hay tareas registradas"
                @page-change="handlePageChange"
            >
                <template #cell-title="{ item }">
                    <div>
                        <div class="font-medium text-gray-900">{{ item.title }}</div>
                        <div class="text-xs text-gray-500">{{ typeLabels[item.type] || item.type }}</div>
                    </div>
                </template>
                <template #cell-due_date="{ value }">
                    <span :class="isOverdue(value) ? 'text-red-600 font-medium' : ''">
                        {{ formatDate(value) }}
                    </span>
                </template>
                <template #cell-status="{ item }">
                    <div class="flex flex-col gap-1">
                        <Badge :color="item.is_published ? 'green' : 'yellow'">
                            {{ item.is_published ? 'Publicada' : 'Borrador' }}
                        </Badge>
                        <div class="text-xs text-gray-500">
                            {{ item.graded_count }}/{{ item.submissions_count }} calificadas
                        </div>
                    </div>
                </template>
            </DataTable>
        </Card>
    </AppLayout>
</template>
