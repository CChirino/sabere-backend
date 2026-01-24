<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

interface SubjectAssignment {
    id: number;
    subject: { id: number; name: string };
    section: { 
        id: number; 
        name: string; 
        grade: { id: number; name: string };
        academic_period: { id: number; name: string };
    };
    academic_period_id: number;
}

const assignments = ref<SubjectAssignment[]>([]);
const loading = ref(true);

const fetchAssignments = async () => {
    try {
        const response = await fetch('/api/v1/subject-assignments?with_section=true', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        assignments.value = data.data || [];
    } catch (error) {
        console.error('Error fetching assignments:', error);
    } finally {
        loading.value = false;
    }
};

// Group assignments by section
const groupedAssignments = computed(() => {
    const groups: Record<number, { section: SubjectAssignment['section']; assignments: SubjectAssignment[] }> = {};
    
    assignments.value.forEach(assignment => {
        const sectionId = assignment.section.id;
        if (!groups[sectionId]) {
            groups[sectionId] = {
                section: assignment.section,
                assignments: [],
            };
        }
        groups[sectionId].assignments.push(assignment);
    });
    
    return Object.values(groups);
});

onMounted(() => {
    fetchAssignments();
});
</script>

<template>
    <Head title="Control de Asistencia" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Control de Asistencia</h1>
                    <p class="text-sm text-gray-600">Registra la asistencia de tus estudiantes</p>
                </div>
            </div>
        </template>

        <!-- Loading -->
        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando secciones...</span>
            </div>
        </Card>

        <!-- No assignments -->
        <Card v-else-if="assignments.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin asignaciones</h3>
            <p class="mt-1 text-gray-500">No tienes materias asignadas para registrar asistencia.</p>
        </Card>

        <!-- Sections Grid -->
        <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <Card 
                v-for="group in groupedAssignments" 
                :key="group.section.id"
                class="hover:shadow-lg transition-shadow"
            >
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ group.section.grade.name }} {{ group.section.name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ group.section.academic_period?.name || 'Período actual' }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 h-10 w-10 bg-sabere-primary/10 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-sabere-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                </div>

                <div class="space-y-2 mb-4">
                    <p class="text-sm text-gray-600">
                        <strong>Materias:</strong>
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <Badge 
                            v-for="assignment in group.assignments" 
                            :key="assignment.id"
                            color="blue"
                            size="sm"
                        >
                            {{ assignment.subject.name }}
                        </Badge>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Link 
                        :href="route('teacher.attendance.section', { id: group.section.id })"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Tomar Asistencia
                    </Link>
                    <Link 
                        :href="route('teacher.attendance.report', { id: group.section.id })"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
                        title="Ver reporte"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </Link>
                </div>
            </Card>
        </div>

    </AppLayout>
</template>
