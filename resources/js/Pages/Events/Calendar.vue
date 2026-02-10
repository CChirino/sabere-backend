<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useApi } from '@/composables/useApi';
import { useAuth } from '@/composables/useAuth';
import type { SchoolEvent } from '@/types';

const { get, loading } = useApi();
const { hasRole } = useAuth();
const events = ref<SchoolEvent[]>([]);

// View mode
const viewMode = ref<'calendar' | 'upcoming'>('calendar');

// Calendar state
const currentDate = ref(new Date());
const currentMonth = computed(() => currentDate.value.getMonth());
const currentYear = computed(() => currentDate.value.getFullYear());

const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

// Panel states
const showViewPanel = ref(false);
const selectedEvent = ref<SchoolEvent | null>(null);

// Filter
const filterType = ref('');

const typeOptions = [
    { value: 'academic', label: 'Académico', color: '#3B82F6' },
    { value: 'sports', label: 'Deportivo', color: '#10B981' },
    { value: 'cultural', label: 'Cultural', color: '#8B5CF6' },
    { value: 'administrative', label: 'Administrativo', color: '#F59E0B' },
];

const visibilityOptions: Record<string, string> = {
    all: 'Todos',
    teachers: 'Solo Profesores',
    students: 'Solo Estudiantes',
    staff: 'Solo Personal',
};

// Calendar helpers
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;

    const days: { date: Date; isCurrentMonth: boolean; isToday: boolean }[] = [];

    for (let i = startDay - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({ date, isCurrentMonth: false, isToday: false });
    }

    const today = new Date();
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        const isToday = date.toDateString() === today.toDateString();
        days.push({ date, isCurrentMonth: true, isToday });
    }

    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        const date = new Date(year, month + 1, i);
        days.push({ date, isCurrentMonth: false, isToday: false });
    }

    return days;
});

const getEventsForDate = (date: Date): SchoolEvent[] => {
    return filteredEvents.value.filter(event => {
        const startDate = new Date(event.start_date);
        const endDate = event.end_date ? new Date(event.end_date) : startDate;

        const dayStart = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const dayEnd = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59);

        return startDate <= dayEnd && endDate >= dayStart;
    });
};

const filteredEvents = computed(() => {
    let result = events.value;
    if (filterType.value) {
        result = result.filter(e => e.type === filterType.value);
    }
    return result;
});

const upcomingEvents = computed(() => {
    const now = new Date();
    return filteredEvents.value
        .filter(e => new Date(e.start_date) >= now)
        .sort((a, b) => new Date(a.start_date).getTime() - new Date(b.start_date).getTime())
        .slice(0, 20);
});

// Navigation
const prevMonth = () => {
    currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
    fetchEvents();
};

const nextMonth = () => {
    currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
    fetchEvents();
};

const goToToday = () => {
    currentDate.value = new Date();
    fetchEvents();
};

// Data fetching
const fetchEvents = async () => {
    const data = await get<SchoolEvent[]>('/api/v1/events');
    if (data) {
        events.value = data;
    }
};

// Format helpers
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getTypeLabel = (type: string) => {
    const option = typeOptions.find(o => o.value === type);
    return option?.label || type;
};

const getTypeColor = (type: string) => {
    const option = typeOptions.find(o => o.value === type);
    return option?.color || '#6B7280';
};

type BadgeColor = 'gray' | 'red' | 'yellow' | 'green' | 'blue' | 'indigo' | 'purple' | 'pink';

const getTypeBadgeColor = (type: string): BadgeColor => {
    const map: Record<string, BadgeColor> = {
        academic: 'blue',
        sports: 'green',
        cultural: 'purple',
        administrative: 'yellow',
    };
    return map[type] || 'gray';
};

const getRelativeDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = date.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Hoy';
    if (diffDays === 1) return 'Mañana';
    if (diffDays > 1 && diffDays <= 7) return `En ${diffDays} días`;
    return formatDate(dateStr);
};

// View event
const viewEvent = (event: SchoolEvent) => {
    selectedEvent.value = event;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedEvent.value = null;
};

onMounted(() => {
    fetchEvents();
});
</script>

<template>
    <Head title="Calendario de Eventos" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Calendario de Eventos</h1>
                <div class="flex items-center space-x-3">
                    <!-- View toggle -->
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                        <button
                            @click="viewMode = 'calendar'"
                            :class="viewMode === 'calendar' ? 'bg-sabere-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                            class="px-3 py-1.5 text-sm font-medium transition-colors"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Calendario
                        </button>
                        <button
                            @click="viewMode = 'upcoming'"
                            :class="viewMode === 'upcoming' ? 'bg-sabere-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                            class="px-3 py-1.5 text-sm font-medium transition-colors"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Próximos
                        </button>
                    </div>
                    <!-- Link to admin if admin/director -->
                    <a
                        v-if="hasRole(['admin', 'director'])"
                        :href="route('admin.events.index')"
                        class="inline-flex items-center px-4 py-2 bg-sabere-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-sabere-primary/90 transition ease-in-out duration-150"
                    >
                        Gestionar Eventos
                    </a>
                </div>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700">Filtrar:</span>
            <button
                @click="filterType = ''"
                :class="filterType === '' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-3 py-1 text-sm rounded-full border transition-colors"
            >
                Todos
            </button>
            <button
                v-for="type in typeOptions"
                :key="type.value"
                @click="filterType = filterType === type.value ? '' : type.value"
                :class="filterType === type.value ? 'text-white' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                :style="filterType === type.value ? { backgroundColor: type.color, borderColor: type.color } : {}"
                class="px-3 py-1 text-sm rounded-full border transition-colors flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: type.color }"></span>
                {{ type.label }}
            </button>
        </div>

        <!-- Calendar View -->
        <Card v-if="viewMode === 'calendar'">
            <!-- Calendar Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ monthNames[currentMonth] }} {{ currentYear }}
                    </h2>
                    <button
                        @click="goToToday"
                        class="px-3 py-1 text-sm font-medium text-sabere-primary bg-sabere-primary/10 rounded-lg hover:bg-sabere-primary/20 transition-colors"
                    >
                        Hoy
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="prevMonth" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button @click="nextMonth" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <!-- Day headers -->
                <div class="grid grid-cols-7 bg-gray-50">
                    <div
                        v-for="day in dayNames"
                        :key="day"
                        class="py-2 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200"
                    >
                        {{ day }}
                    </div>
                </div>

                <!-- Calendar days -->
                <div class="grid grid-cols-7">
                    <div
                        v-for="(day, index) in calendarDays"
                        :key="index"
                        :class="[
                            'min-h-[90px] p-1 border-b border-r border-gray-200',
                            !day.isCurrentMonth ? 'bg-gray-50/50' : 'bg-white',
                            day.isToday ? 'ring-2 ring-inset ring-sabere-accent' : '',
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span
                                :class="[
                                    'text-sm font-medium w-7 h-7 flex items-center justify-center rounded-full',
                                    day.isToday ? 'bg-sabere-accent text-white' : '',
                                    !day.isCurrentMonth ? 'text-gray-400' : 'text-gray-900',
                                ]"
                            >
                                {{ day.date.getDate() }}
                            </span>
                        </div>
                        <div class="space-y-0.5">
                            <div
                                v-for="event in getEventsForDate(day.date).slice(0, 3)"
                                :key="event.id"
                                @click="viewEvent(event)"
                                class="text-xs px-1.5 py-0.5 rounded truncate cursor-pointer hover:opacity-80 transition-opacity"
                                :style="{
                                    backgroundColor: (event.color || getTypeColor(event.type)) + '20',
                                    color: event.color || getTypeColor(event.type),
                                    borderLeft: `3px solid ${event.color || getTypeColor(event.type)}`,
                                }"
                                :title="event.title"
                            >
                                {{ event.title }}
                            </div>
                            <div
                                v-if="getEventsForDate(day.date).length > 3"
                                class="text-xs text-gray-500 px-1.5 font-medium"
                            >
                                +{{ getEventsForDate(day.date).length - 3 }} más
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Upcoming Events View -->
        <Card v-else>
            <div v-if="loading" class="text-center py-12">
                <svg class="h-8 w-8 animate-spin text-sabere-accent mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-500">Cargando eventos...</p>
            </div>

            <div v-else-if="upcomingEvents.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay eventos próximos</h3>
                <p class="mt-1 text-sm text-gray-500">No se encontraron eventos programados.</p>
            </div>

            <div v-else class="space-y-3">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Próximos Eventos</h2>
                <div
                    v-for="event in upcomingEvents"
                    :key="event.id"
                    @click="viewEvent(event)"
                    class="flex items-start space-x-4 p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-sm cursor-pointer transition-all"
                >
                    <!-- Date badge -->
                    <div class="flex-shrink-0 text-center">
                        <div
                            class="w-14 h-14 rounded-lg flex flex-col items-center justify-center"
                            :style="{ backgroundColor: (event.color || getTypeColor(event.type)) + '15' }"
                        >
                            <span class="text-xs font-medium uppercase" :style="{ color: event.color || getTypeColor(event.type) }">
                                {{ new Date(event.start_date).toLocaleDateString('es-VE', { month: 'short' }) }}
                            </span>
                            <span class="text-lg font-bold" :style="{ color: event.color || getTypeColor(event.type) }">
                                {{ new Date(event.start_date).getDate() }}
                            </span>
                        </div>
                    </div>

                    <!-- Event info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ event.title }}</h3>
                            <Badge :color="getTypeBadgeColor(event.type)" size="sm">
                                {{ getTypeLabel(event.type) }}
                            </Badge>
                        </div>
                        <p v-if="event.description" class="text-xs text-gray-600 line-clamp-2 mb-1">{{ event.description }}</p>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ getRelativeDate(event.start_date) }}
                            </span>
                            <span v-if="event.location" class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ event.location }}
                            </span>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <div class="flex-shrink-0 self-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </Card>

        <!-- View Event SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles del Evento" @close="closeViewPanel">
            <div v-if="selectedEvent" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div
                            class="w-3 h-3 rounded-full"
                            :style="{ backgroundColor: selectedEvent.color || getTypeColor(selectedEvent.type) }"
                        ></div>
                        <h4 class="text-lg font-semibold text-gray-900">{{ selectedEvent.title }}</h4>
                    </div>
                    <Badge :color="getTypeBadgeColor(selectedEvent.type)">
                        {{ getTypeLabel(selectedEvent.type) }}
                    </Badge>
                </div>

                <div v-if="selectedEvent.description" class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Descripción</h4>
                    <p class="text-gray-900 whitespace-pre-wrap">{{ selectedEvent.description }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha Inicio</h4>
                        <p class="text-gray-900">
                            {{ selectedEvent.all_day ? formatDate(selectedEvent.start_date) : formatDateTime(selectedEvent.start_date) }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha Fin</h4>
                        <p class="text-gray-900">
                            {{ selectedEvent.end_date
                                ? (selectedEvent.all_day ? formatDate(selectedEvent.end_date) : formatDateTime(selectedEvent.end_date))
                                : 'No definida' }}
                        </p>
                    </div>
                </div>

                <div v-if="selectedEvent.location" class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Ubicación</h4>
                    <p class="text-gray-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ selectedEvent.location }}
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Todo el día</h4>
                    <Badge :color="selectedEvent.all_day ? 'green' : 'gray'">
                        {{ selectedEvent.all_day ? 'Sí' : 'No' }}
                    </Badge>
                </div>

                <div v-if="selectedEvent.creator" class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Publicado por</h4>
                    <p class="text-gray-900">{{ selectedEvent.creator.name }}</p>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
            </template>
        </SlideOver>
    </AppLayout>
</template>
