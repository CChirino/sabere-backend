<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';

const props = defineProps<{
    teacherId: number;
}>();

interface Assignment {
    id: number;
    subject: { id: number; name: string };
    section: { id: number; name: string; grade: { name: string } };
    academic_period: { name: string };
    tasks_count: number;
    pending_submissions: number;
    students_count: number;
}

interface Teacher {
    id: number;
    name: string;
    email: string;
    created_at: string;
    assignments: Assignment[];
    stats: {
        total_assignments: number;
        total_tasks: number;
        total_students: number;
        pending_submissions: number;
    };
}

const teacher = ref<Teacher | null>(null);
const loading = ref(true);

const assignmentColumns = [
    { key: 'subject.name', label: 'Materia' },
    { key: 'section.name', label: 'Sección' },
    { key: 'section.grade.name', label: 'Grado', hideOnMobile: true },
    { key: 'tasks_count', label: 'Tareas' },
    { key: 'pending_submissions', label: 'Pendientes' },
];

const fetchTeacher = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/v1/coordinator/teachers/${props.teacherId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        teacher.value = data.data;
    } catch (error) {
        console.error('Error fetching teacher:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

onMounted(() => {
    fetchTeacher();
});
</script>

<template>
    <Head :title="teacher?.name || 'Detalle del Profesor'" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('coordinator.teachers.index')"
                    class="text-gray-500 hover:text-gray-700"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Detalle del Profesor</h1>
            </div>
        </template>

        <div v-if="loading" class="flex justify-center py-12">
            <svg class="h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div v-else-if="teacher" class="space-y-6">
            <!-- Información del profesor -->
            <Card>
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-2xl font-bold text-blue-600">{{ teacher.name.charAt(0) }}</span>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-gray-900">{{ teacher.name }}</h2>
                            <p class="text-gray-500">{{ teacher.email }}</p>
                            <p class="text-sm text-gray-400 mt-1">Registrado el {{ formatDate(teacher.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Estadísticas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card>
                    <div class="p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ teacher.stats?.total_assignments || 0 }}</div>
                        <div class="text-sm text-gray-500">Asignaciones</div>
                    </div>
                </Card>
                <Card>
                    <div class="p-4 text-center">
                        <div class="text-3xl font-bold text-green-600">{{ teacher.stats?.total_tasks || 0 }}</div>
                        <div class="text-sm text-gray-500">Tareas Creadas</div>
                    </div>
                </Card>
                <Card>
                    <div class="p-4 text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ teacher.stats?.total_students || 0 }}</div>
                        <div class="text-sm text-gray-500">Estudiantes</div>
                    </div>
                </Card>
                <Card>
                    <div class="p-4 text-center">
                        <div class="text-3xl font-bold" :class="teacher.stats?.pending_submissions > 0 ? 'text-yellow-600' : 'text-gray-400'">
                            {{ teacher.stats?.pending_submissions || 0 }}
                        </div>
                        <div class="text-sm text-gray-500">Entregas Pendientes</div>
                    </div>
                </Card>
            </div>

            <!-- Asignaciones -->
            <Card>
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Asignaciones de Materias</h3>
                </div>
                <DataTable
                    :columns="assignmentColumns"
                    :data="teacher.assignments || []"
                    :loading="false"
                    empty-message="No tiene asignaciones activas"
                >
                    <template #cell-tasks_count="{ value }">
                        <Badge :color="value > 0 ? 'blue' : 'gray'">{{ value }}</Badge>
                    </template>
                    <template #cell-pending_submissions="{ value }">
                        <Badge :color="value > 0 ? 'yellow' : 'green'">{{ value }}</Badge>
                    </template>
                    <template #actions="{ item }">
                        <Link
                            :href="route('teacher.scores.assignment', item.id)"
                            class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                        >
                            Ver notas
                        </Link>
                    </template>
                </DataTable>
            </Card>
        </div>

        <div v-else class="text-center py-12 text-gray-500">
            Profesor no encontrado
        </div>
    </AppLayout>
</template>
