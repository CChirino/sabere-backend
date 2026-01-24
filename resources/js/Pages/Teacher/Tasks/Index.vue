<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useAuth } from '@/composables/useAuth';
import type { Task } from '@/types';

const { user } = useAuth();
const tasks = ref<Task[]>([]);
const loading = ref(true);
const pagination = ref<{
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
} | null>(null);

const columns = [
    { key: 'title', label: 'Título' },
    { key: 'subject_assignment.subject.name', label: 'Materia' },
    { key: 'subject_assignment.section.name', label: 'Sección', hideOnMobile: true },
    { key: 'type', label: 'Tipo', hideOnMobile: true },
    { key: 'due_date', label: 'Vencimiento' },
    { key: 'is_published', label: 'Estado' },
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
        const response = await fetch(`/api/v1/tasks?page=${page}&per_page=10`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        tasks.value = data.data || [];
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

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

onMounted(() => {
    fetchTasks();
});
</script>

<template>
    <Head title="Mis Tareas" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Mis Tareas</h1>
                <Link :href="route('teacher.tasks.create')">
                    <PrimaryButton class="w-full sm:w-auto justify-center">Nueva Tarea</PrimaryButton>
                </Link>
            </div>
        </template>

        <Card>
            <DataTable 
                :columns="columns" 
                :data="tasks" 
                :loading="loading" 
                :pagination="pagination"
                empty-message="No hay tareas creadas"
                @page-change="handlePageChange"
            >
                <template #cell-type="{ value }">
                    {{ typeLabels[value] || value }}
                </template>
                <template #cell-due_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-is_published="{ value }">
                    <Badge :color="value ? 'green' : 'yellow'">
                        {{ value ? 'Publicada' : 'Borrador' }}
                    </Badge>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-2">
                        <Link
                            :href="route('teacher.tasks.show', item.id)"
                            class="text-blue-600 hover:text-blue-900"
                        >
                            Ver
                        </Link>
                        <Link
                            :href="route('teacher.tasks.submissions', item.id)"
                            class="text-green-600 hover:text-green-900"
                        >
                            Entregas
                        </Link>
                    </div>
                </template>
            </DataTable>
        </Card>
    </AppLayout>
</template>
