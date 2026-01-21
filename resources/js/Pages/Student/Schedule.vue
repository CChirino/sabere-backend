<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useAuth } from '@/composables/useAuth';
import type { Schedule, Section } from '@/types';

const { user } = useAuth();

const section = ref<Section | null>(null);
const scheduleData = ref<Record<string, Schedule[]>>({});
const loading = ref(true);

const hasAnySchedule = computed(() => {
    return Object.values(scheduleData.value).some(daySchedules => 
        Array.isArray(daySchedules) && daySchedules.length > 0
    );
});

const days = [
    { key: 'monday', label: 'Lunes' },
    { key: 'tuesday', label: 'Martes' },
    { key: 'wednesday', label: 'Miércoles' },
    { key: 'thursday', label: 'Jueves' },
    { key: 'friday', label: 'Viernes' },
];

const fetchSchedule = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/v1/schedules/by-student/${user.value.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        section.value = data.data?.section || null;
        scheduleData.value = data.data?.schedule || {};
    } catch (error) {
        console.error('Error fetching schedule:', error);
    } finally {
        loading.value = false;
    }
};

const formatTime = (time: string) => {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
};

const today = new Date().toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();

const getSchedulesForDay = (dayKey: string): Schedule[] => {
    const dayData = scheduleData.value[dayKey];
    if (!dayData) return [];
    // Handle both array and object formats from Laravel
    if (Array.isArray(dayData)) return dayData;
    if (typeof dayData === 'object') return Object.values(dayData);
    return [];
};

onMounted(() => {
    fetchSchedule();
});
</script>

<template>
    <Head title="Mi Horario" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Mi Horario de Clases</h1>
        </template>

        <div v-if="section" class="mb-6 rounded-lg bg-blue-50 p-4">
            <p class="text-sm font-medium text-blue-800">
                {{ section.grade?.name }} - Sección {{ section.name }}
                <span class="text-blue-600">| {{ section.grade?.education_level?.name }}</span>
            </p>
        </div>

        <!-- Warning if no schedules configured -->
        <div v-if="!loading && section && !hasAnySchedule" class="mb-6 rounded-lg bg-amber-50 border border-amber-200 p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800">Horario no configurado</p>
                    <p class="text-sm text-amber-600">El horario de tu sección aún no ha sido configurado por la administración.</p>
                </div>
            </div>
        </div>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando horario...</span>
            </div>
        </Card>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="day in days"
                :key="day.key"
                :title="day.label"
                :padding="false"
                :class="{ 'ring-2 ring-blue-500': day.key === today }"
            >
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">{{ day.label }}</h3>
                        <Badge v-if="day.key === today" color="blue" size="sm">Hoy</Badge>
                    </div>
                </template>
                <div class="divide-y divide-gray-200">
                    <template v-if="getSchedulesForDay(day.key).length">
                        <div
                            v-for="schedule in getSchedulesForDay(day.key)"
                            :key="schedule.id"
                            class="p-3 hover:bg-gray-50"
                        >
                            <p class="text-sm font-medium text-gray-900">
                                {{ schedule.subject_assignment?.subject?.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Prof. {{ schedule.subject_assignment?.teacher?.name }}
                            </p>
                            <div class="mt-2 flex items-center justify-between">
                                <Badge color="blue" size="sm">
                                    {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                </Badge>
                                <span v-if="schedule.classroom" class="text-xs text-gray-400">
                                    {{ schedule.classroom }}
                                </span>
                            </div>
                        </div>
                    </template>
                    <div v-else class="p-4 text-center text-sm text-gray-400">
                        Sin clases
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
