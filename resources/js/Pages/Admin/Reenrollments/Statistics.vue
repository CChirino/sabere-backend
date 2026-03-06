<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';

interface PeriodStat {
    target_academic_period_id: number;
    status: string;
    count: number;
    target_academic_period: {
        name: string;
    };
}

interface Statistics {
    total_requests: number;
    pending_count: number;
    approved_count: number;
    rejected_count: number;
    by_period: PeriodStat[];
}

const props = defineProps<{
    statistics: Statistics;
}>();

const statusData = computed(() => [
    { name: 'Pendientes', value: props.statistics.pending_count, color: 'bg-yellow-400', textColor: 'text-yellow-600' },
    { name: 'Aprobadas', value: props.statistics.approved_count, color: 'bg-green-500', textColor: 'text-green-600' },
    { name: 'Rechazadas', value: props.statistics.rejected_count, color: 'bg-red-500', textColor: 'text-red-600' },
].filter(item => item.value > 0));

const totalStatus = computed(() => props.statistics.pending_count + props.statistics.approved_count + props.statistics.rejected_count);

const periodData = computed(() => {
    const grouped: Record<string, { period: string; pending: number; approved: number; rejected: number; total: number }> = {};
    
    props.statistics.by_period.forEach(stat => {
        const periodName = stat.target_academic_period.name;
        if (!grouped[periodName]) {
            grouped[periodName] = { period: periodName, pending: 0, approved: 0, rejected: 0, total: 0 };
        }
        grouped[periodName][stat.status as 'pending' | 'approved' | 'rejected'] = stat.count;
        grouped[periodName].total += stat.count;
    });
    
    return Object.values(grouped);
});

const maxPeriodTotal = computed(() => {
    if (periodData.value.length === 0) return 1;
    return Math.max(...periodData.value.map(p => p.total));
});

const approvalRate = computed(() => {
    const total = props.statistics.approved_count + props.statistics.rejected_count;
    if (total === 0) return 0;
    return Math.round((props.statistics.approved_count / total) * 100);
});
</script>

<template>
    <AppLayout>
        <Head title="Estadísticas de Reinscripciones" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <Link
                            :href="route('admin.reenrollments.index')"
                            class="mr-4 text-gray-500 hover:text-gray-700"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Estadísticas de Reinscripciones</h1>
                            <p class="text-gray-600 mt-1">Resumen del proceso de reinscripción.</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card class="bg-blue-50 border-blue-200">
                        <div class="flex items-center p-6">
                            <div class="p-3 rounded-full bg-blue-100 mr-4">
                                <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-600 font-medium">Total Solicitudes</p>
                                <p class="text-3xl font-bold text-blue-700">{{ statistics.total_requests }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="bg-yellow-50 border-yellow-200">
                        <div class="flex items-center p-6">
                            <div class="p-3 rounded-full bg-yellow-100 mr-4">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-yellow-600 font-medium">Pendientes</p>
                                <p class="text-3xl font-bold text-yellow-700">{{ statistics.pending_count }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="bg-green-50 border-green-200">
                        <div class="flex items-center p-6">
                            <div class="p-3 rounded-full bg-green-100 mr-4">
                                <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-green-600 font-medium">Aprobadas</p>
                                <p class="text-3xl font-bold text-green-700">{{ statistics.approved_count }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="bg-red-50 border-red-200">
                        <div class="flex items-center p-6">
                            <div class="p-3 rounded-full bg-red-100 mr-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-red-600 font-medium">Rechazadas</p>
                                <p class="text-3xl font-bold text-red-700">{{ statistics.rejected_count }}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Approval Rate -->
                <Card class="mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tasa de Aprobación</h3>
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-1000"
                                        :style="{ width: `${approvalRate}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div class="ml-4 text-2xl font-bold text-gray-900">{{ approvalRate }}%</div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            Porcentaje de solicitudes aprobadas sobre el total procesadas (aprobadas + rechazadas)
                        </p>
                    </div>
                </Card>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Status Distribution -->
                    <Card>
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribución por Estado</h3>
                            <div v-if="totalStatus === 0" class="text-center py-8 text-gray-500">
                                No hay datos para mostrar
                            </div>
                            <div v-else>
                                <!-- Legend -->
                                <div class="flex justify-center gap-4 mb-6 flex-wrap">
                                    <div v-for="item in statusData" :key="item.name" class="flex items-center">
                                        <div :class="['w-4 h-4 rounded mr-2', item.color]"></div>
                                        <span class="text-sm text-gray-600">{{ item.name }}: {{ item.value }}</span>
                                    </div>
                                </div>
                                <!-- Horizontal Bar Chart -->
                                <div class="space-y-3">
                                    <div v-for="item in statusData" :key="item.name" class="flex items-center">
                                        <span class="w-24 text-sm text-gray-600">{{ item.name }}</span>
                                        <div class="flex-1 ml-2">
                                            <div class="h-8 bg-gray-100 rounded-lg overflow-hidden">
                                                <div
                                                    :class="['h-full transition-all duration-1000', item.color]"
                                                    :style="{ width: `${(item.value / totalStatus) * 100}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                        <span :class="['ml-2 text-sm font-bold', item.textColor]">{{ ((item.value / totalStatus) * 100).toFixed(1) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- By Period -->
                    <Card>
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Por Período Académico</h3>
                            <div v-if="periodData.length === 0" class="text-center py-8 text-gray-500">
                                No hay datos por período
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="period in periodData" :key="period.period" class="border-b pb-4 last:border-0">
                                    <h4 class="font-medium text-gray-900 mb-2">{{ period.period }}</h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <span class="w-20 text-xs text-gray-500">Pendientes</span>
                                            <div class="flex-1 ml-2">
                                                <div class="h-4 bg-gray-100 rounded overflow-hidden">
                                                    <div class="h-full bg-yellow-400 transition-all" :style="{ width: `${(period.pending / maxPeriodTotal) * 100}%` }"></div>
                                                </div>
                                            </div>
                                            <span class="ml-2 text-xs font-medium">{{ period.pending }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-20 text-xs text-gray-500">Aprobadas</span>
                                            <div class="flex-1 ml-2">
                                                <div class="h-4 bg-gray-100 rounded overflow-hidden">
                                                    <div class="h-full bg-green-500 transition-all" :style="{ width: `${(period.approved / maxPeriodTotal) * 100}%` }"></div>
                                                </div>
                                            </div>
                                            <span class="ml-2 text-xs font-medium">{{ period.approved }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="w-20 text-xs text-gray-500">Rechazadas</span>
                                            <div class="flex-1 ml-2">
                                                <div class="h-4 bg-gray-100 rounded overflow-hidden">
                                                    <div class="h-full bg-red-500 transition-all" :style="{ width: `${(period.rejected / maxPeriodTotal) * 100}%` }"></div>
                                                </div>
                                            </div>
                                            <span class="ml-2 text-xs font-medium">{{ period.rejected }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
