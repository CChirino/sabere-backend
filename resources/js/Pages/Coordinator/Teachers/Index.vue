<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';

interface Teacher {
    id: number;
    name: string;
    email: string;
    assignments_count: number;
    sections: string[];
    subjects: string[];
}

const teachers = ref<Teacher[]>([]);
const loading = ref(true);
const searchQuery = ref('');
const pagination = ref<{
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
} | null>(null);

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Email', hideOnMobile: true },
    { key: 'assignments_count', label: 'Asignaciones' },
    { key: 'subjects', label: 'Materias', hideOnMobile: true },
];

const fetchTeachers = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page),
            per_page: '10',
            role: 'teacher',
        });
        if (searchQuery.value) {
            params.append('search', searchQuery.value);
        }
        
        const response = await fetch(`/api/v1/coordinator/teachers?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        teachers.value = data.data || [];
        pagination.value = data.pagination || null;
    } catch (error) {
        console.error('Error fetching teachers:', error);
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (page: number) => {
    fetchTeachers(page);
};

const handleSearch = () => {
    fetchTeachers(1);
};

onMounted(() => {
    fetchTeachers();
});
</script>

<template>
    <Head title="Gestión de Profesores" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Profesores</h1>
                <div class="flex items-center gap-2">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Buscar profesor..."
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        @keyup.enter="handleSearch"
                    />
                    <button
                        @click="handleSearch"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Buscar
                    </button>
                </div>
            </div>
        </template>

        <Card>
            <DataTable 
                :columns="columns" 
                :data="teachers" 
                :loading="loading"
                :pagination="pagination"
                empty-message="No hay profesores registrados"
                @page-change="handlePageChange"
            >
                <template #cell-name="{ item }">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-semibold">{{ item.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ item.name }}</div>
                            <div class="text-sm text-gray-500 md:hidden">{{ item.email }}</div>
                        </div>
                    </div>
                </template>
                <template #cell-assignments_count="{ value }">
                    <Badge :color="value > 0 ? 'blue' : 'gray'">
                        {{ value }} {{ value === 1 ? 'asignación' : 'asignaciones' }}
                    </Badge>
                </template>
                <template #cell-subjects="{ value }">
                    <div class="flex flex-wrap gap-1">
                        <Badge v-for="subject in (value || []).slice(0, 3)" :key="subject" color="green">
                            {{ subject }}
                        </Badge>
                        <Badge v-if="(value || []).length > 3" color="gray">
                            +{{ value.length - 3 }}
                        </Badge>
                    </div>
                </template>
                <template #actions="{ item }">
                    <Link
                        :href="route('coordinator.teachers.show', item.id)"
                        class="text-blue-600 hover:text-blue-900 font-medium"
                    >
                        Ver detalle
                    </Link>
                </template>
            </DataTable>
        </Card>

        <!-- Resumen -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Total Profesores</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">{{ pagination?.total || 0 }}</div>
                </div>
            </Card>
            <Card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Con Asignaciones</div>
                    <div class="mt-1 text-3xl font-bold text-green-600">
                        {{ teachers.filter(t => t.assignments_count > 0).length }}
                    </div>
                </div>
            </Card>
            <Card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Sin Asignaciones</div>
                    <div class="mt-1 text-3xl font-bold text-yellow-600">
                        {{ teachers.filter(t => t.assignments_count === 0).length }}
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
