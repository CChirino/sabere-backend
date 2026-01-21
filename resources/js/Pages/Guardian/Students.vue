<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useAuth } from '@/composables/useAuth';
import type { User, Enrollment } from '@/types';

interface StudentWithEnrollments extends User {
    enrollments?: Enrollment[];
}

const { user } = useAuth();
const students = ref<StudentWithEnrollments[]>([]);
const loading = ref(true);

const fetchStudents = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/v1/guardian/my-students', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        students.value = data.data || [];
    } catch (error) {
        console.error('Error fetching students:', error);
    } finally {
        loading.value = false;
    }
};

const getActiveEnrollment = (student: StudentWithEnrollments): Enrollment | undefined => {
    return student.enrollments?.find(e => e.status === 'active') || student.enrollments?.[0];
};

const getScoreColor = (score?: number): 'green' | 'yellow' | 'red' | 'gray' => {
    if (!score) return 'gray';
    if (score >= 16) return 'green';
    if (score >= 10) return 'yellow';
    return 'red';
};

onMounted(() => {
    fetchStudents();
});
</script>

<template>
    <Head title="Mis Representados" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Mis Representados</h1>
        </template>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando...</span>
            </div>
        </Card>

        <div v-else-if="students.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin representados</h3>
            <p class="mt-1 text-gray-500">No tienes estudiantes registrados como representados.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="student in students" :key="student.id" class="hover:shadow-lg transition-shadow">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-sabere-dark/10">
                        <span class="text-xl font-semibold text-sabere-dark">
                            {{ student.name?.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ student.name }}</h3>
                        <p v-if="getActiveEnrollment(student)" class="text-sm text-gray-500">
                            {{ getActiveEnrollment(student)?.section?.grade?.name }} - Sección {{ getActiveEnrollment(student)?.section?.name }}
                        </p>
                        <p v-else class="text-sm text-amber-600">Sin inscripción activa</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    <Link
                        :href="`/guardian/students/${student.id}/scores`"
                        class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-sm font-medium hover:bg-green-200 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Boleta
                    </Link>
                    <Link
                        :href="`/guardian/students/${student.id}/tasks`"
                        class="inline-flex items-center px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-sm font-medium hover:bg-amber-200 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Tareas
                    </Link>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
