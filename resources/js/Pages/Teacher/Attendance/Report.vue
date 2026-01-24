<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps<{
    sectionId: number;
}>();

interface StudentReport {
    student_id: number;
    student_name: string;
    student_email: string;
    total_classes: number;
    present: number;
    absent: number;
    late: number;
    excused: number;
    attended: number;
    percentage: number;
    meets_requirement: boolean;
}

interface Section {
    id: number;
    name: string;
    grade: { id: number; name: string };
    academic_period: { id: number; name: string };
    academic_period_id: number;
}

interface SubjectAssignment {
    id: number;
    subject: { id: number; name: string };
}

interface HistoryRecord {
    date: string;
    total: number;
    present: number;
    absent: number;
    late: number;
    excused: number;
    recorded_by: string;
}

const section = ref<Section | null>(null);
const assignments = ref<SubjectAssignment[]>([]);
const report = ref<StudentReport[]>([]);
const history = ref<HistoryRecord[]>([]);
const summary = ref({ total_students: 0, at_risk_count: 0, passing_count: 0 });
const selectedAssignmentId = ref<number | null>(null);
const loading = ref(true);
const activeTab = ref<'report' | 'history'>('report');

const fetchSection = async () => {
    try {
        const response = await fetch(`/api/v1/sections/${props.sectionId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        section.value = data.data;
    } catch (error) {
        console.error('Error fetching section:', error);
    }
};

const fetchAssignments = async () => {
    try {
        const response = await fetch(`/api/v1/subject-assignments?section_id=${props.sectionId}`, {
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
    }
};

const fetchReport = async () => {
    loading.value = true;
    try {
        let url = `/api/v1/attendance/section/${props.sectionId}/report`;
        if (selectedAssignmentId.value) {
            url += `?subject_assignment_id=${selectedAssignmentId.value}`;
        }
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        report.value = data.report || [];
        summary.value = data.summary || { total_students: 0, at_risk_count: 0, passing_count: 0 };
    } catch (error) {
        console.error('Error fetching report:', error);
    } finally {
        loading.value = false;
    }
};

const fetchHistory = async () => {
    try {
        let url = `/api/v1/attendance/section/${props.sectionId}/history`;
        if (selectedAssignmentId.value) {
            url += `?subject_assignment_id=${selectedAssignmentId.value}`;
        }
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        history.value = data.history || [];
    } catch (error) {
        console.error('Error fetching history:', error);
    }
};

const getPercentageColor = (percentage: number) => {
    if (percentage >= 90) return 'text-green-600';
    if (percentage >= 75) return 'text-blue-600';
    if (percentage >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getPercentageBadge = (percentage: number): 'green' | 'blue' | 'yellow' | 'red' => {
    if (percentage >= 90) return 'green';
    if (percentage >= 75) return 'blue';
    if (percentage >= 60) return 'yellow';
    return 'red';
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-VE', { 
        weekday: 'short', 
        day: 'numeric', 
        month: 'short' 
    });
};

const sortedReport = computed(() => {
    return [...report.value].sort((a, b) => a.percentage - b.percentage);
});

const atRiskStudents = computed(() => {
    return report.value.filter(r => !r.meets_requirement);
});

watch(selectedAssignmentId, () => {
    fetchReport();
    fetchHistory();
});

onMounted(async () => {
    await Promise.all([fetchSection(), fetchAssignments()]);
    await Promise.all([fetchReport(), fetchHistory()]);
});
</script>

<template>
    <Head :title="`Reporte Asistencia - ${section?.grade?.name || ''} ${section?.name || ''}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <Link :href="route('teacher.attendance.index')" class="text-gray-500 hover:text-gray-700 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="min-w-0">
                        <h1 class="text-xl font-bold text-gray-900 truncate">
                            Reporte de Asistencia
                        </h1>
                        <p v-if="section" class="text-sm text-gray-600 truncate">
                            {{ section.grade?.name }} {{ section.name }}
                        </p>
                    </div>
                </div>
                <Link 
                    :href="route('teacher.attendance.section', { id: sectionId })"
                    class="hidden sm:inline-flex items-center px-4 py-2 bg-sabere-primary text-white rounded-lg text-sm font-medium hover:bg-sabere-dark transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Tomar Asistencia
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <Card class="mb-4">
            <div class="flex flex-wrap items-center gap-4">
                <div v-if="assignments.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por materia</label>
                    <select 
                        v-model="selectedAssignmentId"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-sabere-primary focus:border-sabere-primary min-w-[200px]"
                    >
                        <option :value="null">Todas las materias</option>
                        <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                            {{ assignment.subject.name }}
                        </option>
                    </select>
                </div>
                <div class="flex-1"></div>
                <!-- Tabs -->
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button 
                        @click="activeTab = 'report'"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                            activeTab === 'report' ? 'bg-white shadow text-sabere-primary' : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Reporte
                    </button>
                    <button 
                        @click="activeTab = 'history'"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                            activeTab === 'history' ? 'bg-white shadow text-sabere-primary' : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        Historial
                    </button>
                </div>
            </div>
        </Card>

        <!-- Summary -->
        <Card class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div class="flex items-center gap-8">
                    <div class="text-center">
                        <span class="block text-3xl font-bold text-gray-900">{{ summary.total_students }}</span>
                        <span class="text-sm text-gray-500">Estudiantes</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-3xl font-bold text-green-600">{{ summary.passing_count }}</span>
                        <span class="text-sm text-gray-500">Cumplen (≥75%)</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-3xl font-bold text-red-600">{{ summary.at_risk_count }}</span>
                        <span class="text-sm text-gray-500">En riesgo (&lt;75%)</span>
                    </div>
                </div>
                <div v-if="summary.at_risk_count > 0" class="bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                    <div class="flex items-center text-red-800">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="font-medium">{{ summary.at_risk_count }} estudiante(s) no cumplen con el 75% de asistencia</span>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Loading -->
        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando reporte...</span>
            </div>
        </Card>

        <!-- Report Tab -->
        <template v-else-if="activeTab === 'report'">
            <!-- At Risk Students Alert -->
            <Card v-if="atRiskStudents.length > 0" class="mb-4 bg-red-50 border-red-200">
                <h3 class="text-lg font-semibold text-red-800 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Estudiantes en Riesgo de Reprobación por Inasistencia
                </h3>
                <div class="space-y-2">
                    <div 
                        v-for="student in atRiskStudents" 
                        :key="student.student_id"
                        class="flex items-center justify-between bg-white rounded-lg px-4 py-2 border border-red-200"
                    >
                        <div>
                            <span class="font-medium text-gray-900">{{ student.student_name }}</span>
                            <span class="text-sm text-gray-500 ml-2">{{ student.student_email }}</span>
                        </div>
                        <Badge color="red">{{ student.percentage.toFixed(1) }}% asistencia</Badge>
                    </div>
                </div>
            </Card>

            <!-- Report Table -->
            <Card v-if="report.length > 0" :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estudiante
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Clases
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-green-600 uppercase tracking-wider">
                                    Presente
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-red-600 uppercase tracking-wider">
                                    Ausente
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-yellow-600 uppercase tracking-wider">
                                    Tardanza
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-blue-600 uppercase tracking-wider">
                                    Justificado
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                                    % Asistencia
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="student in sortedReport" 
                                :key="student.student_id"
                                :class="{ 'bg-red-50': !student.meets_requirement }"
                            >
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-sabere-primary/10 rounded-full flex items-center justify-center">
                                            <span class="text-sabere-primary text-sm font-medium">
                                                {{ student.student_name?.charAt(0) }}
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ student.student_name }}</div>
                                            <div class="text-xs text-gray-500">{{ student.student_email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-700">
                                    {{ student.total_classes }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-green-600">
                                    {{ student.present }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-red-600">
                                    {{ student.absent }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-yellow-600">
                                    {{ student.late }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium text-blue-600">
                                    {{ student.excused }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center bg-gray-50">
                                    <span :class="['text-lg font-bold', getPercentageColor(student.percentage)]">
                                        {{ student.percentage.toFixed(1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <Badge :color="student.meets_requirement ? 'green' : 'red'">
                                        {{ student.meets_requirement ? 'Cumple' : 'En riesgo' }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- No data -->
            <Card v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Sin datos de asistencia</h3>
                <p class="mt-1 text-gray-500">Aún no se ha registrado asistencia para esta sección.</p>
                <Link 
                    :href="route('teacher.attendance.section', { id: sectionId })"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-sabere-primary text-white rounded-lg text-sm font-medium hover:bg-sabere-dark transition-colors"
                >
                    Tomar Asistencia
                </Link>
            </Card>
        </template>

        <!-- History Tab -->
        <template v-else-if="activeTab === 'history'">
            <Card v-if="history.length > 0" :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-green-600 uppercase tracking-wider">
                                    Presentes
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-red-600 uppercase tracking-wider">
                                    Ausentes
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-yellow-600 uppercase tracking-wider">
                                    Tardanzas
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-blue-600 uppercase tracking-wider">
                                    Justificados
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Registrado por
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="record in history" :key="record.date" class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ formatDate(record.date) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-700">
                                    {{ record.total }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ record.present }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ record.absent }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ record.late }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ record.excused }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ record.recorded_by }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- No history -->
            <Card v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Sin historial</h3>
                <p class="mt-1 text-gray-500">No hay registros de asistencia en el período seleccionado.</p>
            </Card>
        </template>

        <!-- Legend -->
        <Card class="mt-4 bg-blue-50 border-blue-200">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800">
                    <p><strong>Cálculo de asistencia:</strong> Se considera como asistencia válida: Presente + Tardanza + Justificado.</p>
                    <p class="mt-1">Los estudiantes con menos del <strong>75%</strong> de asistencia están en riesgo de no aprobar el grado o asignatura.</p>
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
