<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useAuth } from '@/composables/useAuth';
import type { Task } from '@/types';

const { user } = useAuth();

const tasks = ref<Task[]>([]);
const loading = ref(true);
const filter = ref<'all' | 'pending' | 'submitted' | 'graded'>('all');

const fetchTasks = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/v1/tasks/for-student/${user.value.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        tasks.value = data.data || [];
    } catch (error) {
        console.error('Error fetching tasks:', error);
    } finally {
        loading.value = false;
    }
};

const filteredTasks = computed(() => {
    if (filter.value === 'all') return tasks.value;
    return tasks.value.filter(task => {
        if (filter.value === 'pending') return !task.submission_status || task.submission_status === 'pending';
        return task.submission_status === filter.value;
    });
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const getTaskTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        homework: 'Tarea',
        exam: 'Examen',
        quiz: 'Quiz',
        project: 'Proyecto',
        activity: 'Actividad',
    };
    return labels[type] || type;
};

const getTaskTypeColor = (type: string): 'blue' | 'red' | 'yellow' | 'purple' | 'green' => {
    const colors: Record<string, 'blue' | 'red' | 'yellow' | 'purple' | 'green'> = {
        homework: 'blue',
        exam: 'red',
        quiz: 'yellow',
        project: 'purple',
        activity: 'green',
    };
    return colors[type] || 'blue';
};

const getStatusColor = (status?: string): 'gray' | 'yellow' | 'green' | 'red' => {
    if (!status || status === 'pending') return 'gray';
    if (status === 'submitted' || status === 'late') return 'yellow';
    if (status === 'graded') return 'green';
    return 'red';
};

const getStatusLabel = (status?: string) => {
    const labels: Record<string, string> = {
        pending: 'Pendiente',
        submitted: 'Entregada',
        late: 'Entrega tardía',
        graded: 'Calificada',
        returned: 'Devuelta',
    };
    return labels[status || 'pending'] || 'Pendiente';
};

const isOverdue = (dueDate?: string) => {
    if (!dueDate) return false;
    return new Date(dueDate) < new Date();
};

onMounted(() => {
    fetchTasks();
});
</script>

<template>
    <Head title="Mis Tareas" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Mis Tareas</h1>
        </template>

        <!-- Filtros -->
        <div class="mb-6 flex space-x-2">
            <button
                v-for="f in [
                    { key: 'all', label: 'Todas' },
                    { key: 'pending', label: 'Pendientes' },
                    { key: 'submitted', label: 'Entregadas' },
                    { key: 'graded', label: 'Calificadas' },
                ]"
                :key="f.key"
                @click="filter = f.key as any"
                :class="[
                    filter === f.key
                        ? 'bg-blue-600 text-white'
                        : 'bg-white text-gray-700 hover:bg-gray-50',
                    'rounded-md px-4 py-2 text-sm font-medium shadow-sm border border-gray-300'
                ]"
            >
                {{ f.label }}
            </button>
        </div>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando tareas...</span>
            </div>
        </Card>

        <div v-else-if="filteredTasks.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No hay tareas</h3>
            <p class="mt-1 text-gray-500">No tienes tareas en esta categoría.</p>
        </div>

        <div v-else class="space-y-4">
            <Card v-for="task in filteredTasks" :key="task.id" :padding="false">
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h3 class="text-lg font-medium text-gray-900">{{ task.title }}</h3>
                                <Badge :color="getTaskTypeColor(task.type)" size="sm">
                                    {{ getTaskTypeLabel(task.type) }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ task.subject_assignment?.subject?.name }}
                            </p>
                            <p v-if="task.description" class="mt-2 text-sm text-gray-600">
                                {{ task.description }}
                            </p>
                        </div>
                        <div class="text-right">
                            <Badge :color="getStatusColor(task.submission_status)">
                                {{ getStatusLabel(task.submission_status) }}
                            </Badge>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span>
                                <strong>Puntos:</strong> {{ task.max_score }}
                            </span>
                            <span v-if="task.due_date" :class="{ 'text-red-600': isOverdue(task.due_date) && !task.submission_status }">
                                <strong>Vence:</strong> {{ formatDate(task.due_date) }}
                                <span v-if="isOverdue(task.due_date) && !task.submission_status" class="text-red-600">(Vencida)</span>
                            </span>
                        </div>
                        <Link
                            :href="route('student.tasks.show', task.id)"
                            class="text-blue-600 hover:text-blue-900 font-medium text-sm"
                        >
                            Ver detalles →
                        </Link>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
