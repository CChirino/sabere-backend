<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';
import { Head } from '@inertiajs/vue3';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { computed, onMounted, ref } from 'vue';
import { Bar, Line, Pie } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement);

defineProps<{ academicPeriods: any[] }>();

const { get } = useApi();
const indicators = ref<any>(null);
const selectedPeriod = ref<string>('');

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const attendanceChartData = computed(() => {
    const rows = (indicators.value?.attendance_by_section ?? []) as any[];

    return {
        labels: rows.map((r: any) => r.section_name as string),
        datasets: [
            {
                label: '% Asistencia',
                data: rows.map((r: any) => Number(r.percentage)) as number[],
                backgroundColor: '#3b82f6',
            },
        ],
    };
});

const scoresBySectionChartData = computed(() => {
    const rows = (indicators.value?.scores_by_section ?? []) as any[];

    return {
        labels: rows.map((r: any) => r.section_name as string),
        datasets: [
            {
                label: 'Promedio',
                data: rows.map((r: any) => Number(r.average)) as number[],
                backgroundColor: '#10b981',
            },
        ],
    };
});

const disciplinaryChartData = computed(() => {
    const data = (indicators.value?.disciplinary_by_severity ?? { leve: 0, moderada: 0, grave: 0 }) as Record<string, number>;

    return {
        labels: Object.keys(data),
        datasets: [
            {
                data: Object.values(data) as number[],
                backgroundColor: ['#f59e0b', '#f97316', '#ef4444'],
            },
        ],
    };
});

const admissionsChartData = computed(() => {
    const data = (indicators.value?.admissions_by_status ?? { pending: 0, approved: 0, rejected: 0 }) as Record<string, number>;

    return {
        labels: Object.keys(data),
        datasets: [
            {
                label: 'Admisiones',
                data: Object.values(data) as number[],
                backgroundColor: ['#3b82f6', '#10b981', '#ef4444'],
            },
        ],
    };
});

const loadIndicators = async () => {
    let url = '/api/v1/dashboard/indicators';
    if (selectedPeriod.value) {
        url += `?academic_period_id=${selectedPeriod.value}`;
    }
    indicators.value = await get(url);
};

onMounted(loadIndicators);
</script>

<template>
    <AppLayout>
        <Head title="Indicadores" />

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Indicadores</h1>

                    <select v-model="selectedPeriod" class="rounded-md border-gray-300 shadow-sm" @change="loadIndicators">
                        <option value="">Período activo</option>
                        <option v-for="period in academicPeriods" :key="period.id" :value="period.id">
                            {{ period.name }}
                        </option>
                    </select>
                </div>

                <div v-if="indicators" class="space-y-6">
                    <!-- Cards -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-white p-6 shadow">
                            <p class="text-sm text-gray-500">Justificativos pendientes</p>
                            <p class="text-2xl font-bold">{{ indicators.pending_justifications }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow">
                            <p class="text-sm text-gray-500">Estudiantes en riesgo</p>
                            <p class="text-2xl font-bold">{{ indicators.at_risk_students.length }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow">
                            <p class="text-sm text-gray-500">Incidencias graves</p>
                            <p class="text-2xl font-bold">{{ indicators.disciplinary_by_severity.grave }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow">
                            <p class="text-sm text-gray-500">Admisiones pendientes</p>
                            <p class="text-2xl font-bold">{{ indicators.admissions_by_status.pending }}</p>
                        </div>
                    </div>

                    <!-- Gráficas -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h2 class="mb-4 text-lg font-semibold">Asistencia por sección</h2>
                            <div class="h-64">
                                <Bar :data="attendanceChartData" :options="chartOptions" />
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow">
                            <h2 class="mb-4 text-lg font-semibold">Promedio por sección</h2>
                            <div class="h-64">
                                <Bar :data="scoresBySectionChartData" :options="chartOptions" />
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow">
                            <h2 class="mb-4 text-lg font-semibold">Incidencias por gravedad</h2>
                            <div class="h-64">
                                <Pie :data="disciplinaryChartData" :options="chartOptions" />
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow">
                            <h2 class="mb-4 text-lg font-semibold">Admisiones por estado</h2>
                            <div class="h-64">
                                <Bar :data="admissionsChartData" :options="chartOptions" />
                            </div>
                        </div>
                    </div>

                    <!-- Tabla riesgo -->
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h2 class="mb-4 text-lg font-semibold">Estudiantes en riesgo</h2>
                        <table v-if="indicators.at_risk_students.length" class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Estudiante</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Sección</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Asistencia</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Promedio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="student in indicators.at_risk_students" :key="student.student_id">
                                    <td class="px-4 py-2 text-sm">{{ student.student_name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ student.section_name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ student.attendance_percentage }}%</td>
                                    <td class="px-4 py-2 text-sm">{{ student.score_average ?? 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-gray-500">No hay estudiantes en riesgo.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
