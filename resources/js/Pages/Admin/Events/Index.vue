<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useApi } from '@/composables/useApi';
import type { SchoolEvent, AcademicPeriod } from '@/types';

const { get, loading } = useApi();
const events = ref<SchoolEvent[]>([]);
const periods = ref<AcademicPeriod[]>([]);

// View mode
const viewMode = ref<'calendar' | 'list'>('calendar');

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
const showCreatePanel = ref(false);
const showEditPanel = ref(false);
const showViewPanel = ref(false);
const showDeleteModal = ref(false);
const selectedEvent = ref<SchoolEvent | null>(null);
const deleting = ref(false);

// Filter
const filterType = ref('');

const typeOptions = [
    { value: 'academic', label: 'Académico', color: '#3B82F6' },
    { value: 'sports', label: 'Deportivo', color: '#10B981' },
    { value: 'cultural', label: 'Cultural', color: '#8B5CF6' },
    { value: 'administrative', label: 'Administrativo', color: '#F59E0B' },
];

const visibilityOptions = [
    { value: 'all', label: 'Todos' },
    { value: 'teachers', label: 'Solo Profesores' },
    { value: 'students', label: 'Solo Estudiantes' },
    { value: 'staff', label: 'Solo Personal' },
];

// Create form
const createForm = useForm({
    title: '',
    description: '',
    type: 'academic' as string,
    start_date: '',
    end_date: '',
    all_day: false,
    location: '',
    color: '',
    visibility: 'all' as string,
    academic_period_id: '' as string | number,
    send_notification: false,
    status: true,
});

// Edit form
const editForm = useForm({
    title: '',
    description: '',
    type: 'academic' as string,
    start_date: '',
    end_date: '',
    all_day: false,
    location: '',
    color: '',
    visibility: 'all' as string,
    academic_period_id: '' as string | number,
    send_notification: false,
    status: true,
});

// Calendar helpers
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    // Adjust to start on Monday (0=Mon, 6=Sun)
    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;
    
    const days: { date: Date; isCurrentMonth: boolean; isToday: boolean }[] = [];
    
    // Previous month days
    for (let i = startDay - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({ date, isCurrentMonth: false, isToday: false });
    }
    
    // Current month days
    const today = new Date();
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        const isToday = date.toDateString() === today.toDateString();
        days.push({ date, isCurrentMonth: true, isToday });
    }
    
    // Next month days to fill grid
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

const sortedEvents = computed(() => {
    return [...filteredEvents.value].sort((a, b) => 
        new Date(a.start_date).getTime() - new Date(b.start_date).getTime()
    );
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

const fetchPeriods = async () => {
    const data = await get<AcademicPeriod[]>('/api/v1/academic-periods');
    if (data) {
        periods.value = data;
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

const formatDateForInput = (date: string) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toISOString().slice(0, 16);
};

const formatDateForDateInput = (date: string) => {
    if (!date) return '';
    return date.split('T')[0];
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

const getVisibilityLabel = (visibility: string) => {
    const option = visibilityOptions.find(o => o.value === visibility);
    return option?.label || visibility;
};

// CRUD operations
const openCreatePanel = (date?: Date) => {
    createForm.reset();
    createForm.type = 'academic';
    createForm.visibility = 'all';
    createForm.status = true;
    if (date) {
        const dateStr = date.toISOString().slice(0, 10);
        createForm.start_date = dateStr + 'T08:00';
        createForm.end_date = dateStr + 'T17:00';
    }
    showCreatePanel.value = true;
};

const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('admin.events.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreatePanel();
            fetchEvents();
        },
    });
};

const viewEvent = (event: SchoolEvent) => {
    selectedEvent.value = event;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedEvent.value = null;
};

const editEvent = (event: SchoolEvent) => {
    selectedEvent.value = event;
    editForm.title = event.title;
    editForm.description = event.description || '';
    editForm.type = event.type;
    editForm.start_date = formatDateForInput(event.start_date);
    editForm.end_date = event.end_date ? formatDateForInput(event.end_date) : '';
    editForm.all_day = event.all_day;
    editForm.location = event.location || '';
    editForm.color = event.color || '';
    editForm.visibility = event.visibility;
    editForm.academic_period_id = event.academic_period_id || '';
    editForm.send_notification = event.send_notification;
    editForm.status = event.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedEvent.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!selectedEvent.value) return;
    editForm.put(route('admin.events.update', selectedEvent.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditPanel();
            fetchEvents();
        },
    });
};

const confirmDelete = (event: SchoolEvent) => {
    selectedEvent.value = event;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedEvent.value = null;
};

const deleteEvent = () => {
    if (!selectedEvent.value) return;
    deleting.value = true;
    router.delete(route('admin.events.destroy', selectedEvent.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            fetchEvents();
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

onMounted(() => {
    fetchEvents();
    fetchPeriods();
});
</script>

<template>
    <Head title="Gestión de Eventos" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Gestión de Eventos</h1>
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
                            @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'bg-sabere-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                            class="px-3 py-1.5 text-sm font-medium transition-colors"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Lista
                        </button>
                    </div>
                    <PrimaryButton @click="openCreatePanel()">Nuevo Evento</PrimaryButton>
                </div>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700">Filtrar por tipo:</span>
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
                    <button
                        @click="prevMonth"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        @click="nextMonth"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
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
                            'min-h-[100px] p-1 border-b border-r border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors',
                            !day.isCurrentMonth ? 'bg-gray-50/50' : 'bg-white',
                            day.isToday ? 'ring-2 ring-inset ring-sabere-accent' : '',
                        ]"
                        @click="openCreatePanel(day.date)"
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
                                @click.stop="viewEvent(event)"
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

        <!-- List View -->
        <Card v-else>
            <div v-if="loading" class="text-center py-12">
                <svg class="h-8 w-8 animate-spin text-sabere-accent mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-500">Cargando eventos...</p>
            </div>

            <div v-else-if="sortedEvents.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay eventos</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo evento.</p>
            </div>

            <div v-else class="divide-y divide-gray-200">
                <div
                    v-for="event in sortedEvents"
                    :key="event.id"
                    class="flex items-center justify-between py-4 px-2 hover:bg-gray-50 rounded-lg transition-colors"
                >
                    <div class="flex items-center space-x-4 min-w-0 flex-1">
                        <div
                            class="w-1 h-12 rounded-full flex-shrink-0"
                            :style="{ backgroundColor: event.color || getTypeColor(event.type) }"
                        ></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-semibold text-gray-900 truncate">{{ event.title }}</h3>
                                <Badge :color="getTypeBadgeColor(event.type)" size="sm">
                                    {{ getTypeLabel(event.type) }}
                                </Badge>
                                <Badge v-if="!event.status" color="gray" size="sm">Inactivo</Badge>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ event.all_day ? formatDate(event.start_date) : formatDateTime(event.start_date) }}
                                    <template v-if="event.end_date">
                                        — {{ event.all_day ? formatDate(event.end_date) : formatDateTime(event.end_date) }}
                                    </template>
                                </span>
                                <span v-if="event.location" class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ event.location }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ getVisibilityLabel(event.visibility) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 flex-shrink-0 ml-4">
                        <button @click="viewEvent(event)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                            Ver
                        </button>
                        <button @click="editEvent(event)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                            Editar
                        </button>
                        <button @click="confirmDelete(event)" class="text-red-600 hover:text-red-900 font-medium text-sm">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Card>

        <!-- View SlideOver -->
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

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Ubicación</h4>
                        <p class="text-gray-900">{{ selectedEvent.location || 'No especificada' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Visibilidad</h4>
                        <p class="text-gray-900">{{ getVisibilityLabel(selectedEvent.visibility) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Todo el día</h4>
                        <Badge :color="selectedEvent.all_day ? 'green' : 'gray'">
                            {{ selectedEvent.all_day ? 'Sí' : 'No' }}
                        </Badge>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                        <Badge :color="selectedEvent.status ? 'green' : 'gray'">
                            {{ selectedEvent.status ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </div>
                </div>

                <div v-if="selectedEvent.creator" class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Creado por</h4>
                    <p class="text-gray-900">{{ selectedEvent.creator.name }}</p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <div class="flex space-x-2">
                        <DangerButton @click="() => { const e = selectedEvent!; closeViewPanel(); confirmDelete(e); }">Eliminar</DangerButton>
                        <PrimaryButton @click="() => { const e = selectedEvent!; closeViewPanel(); editEvent(e); }">Editar</PrimaryButton>
                    </div>
                </div>
            </template>
        </SlideOver>

        <!-- Create SlideOver -->
        <SlideOver :show="showCreatePanel" title="Nuevo Evento" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-title" value="Título" />
                    <TextInput id="create-title" v-model="createForm.title" type="text" class="mt-1 block w-full" placeholder="Ej: Día del Estudiante" required />
                    <InputError :message="createForm.errors.title" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="create-description" value="Descripción" />
                    <textarea
                        id="create-description"
                        v-model="createForm.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Descripción del evento..."
                    ></textarea>
                    <InputError :message="createForm.errors.description" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-type" value="Tipo" />
                        <select
                            id="create-type"
                            v-model="createForm.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option v-for="type in typeOptions" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.type" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-visibility" value="Visibilidad" />
                        <select
                            id="create-visibility"
                            v-model="createForm.visibility"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option v-for="vis in visibilityOptions" :key="vis.value" :value="vis.value">
                                {{ vis.label }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.visibility" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center mb-2">
                    <Checkbox id="create-all_day" v-model:checked="createForm.all_day" />
                    <InputLabel for="create-all_day" value="Todo el día" class="ml-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-start_date" value="Fecha Inicio" />
                        <TextInput
                            id="create-start_date"
                            v-model="createForm.start_date"
                            :type="createForm.all_day ? 'date' : 'datetime-local'"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="createForm.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-end_date" value="Fecha Fin" />
                        <TextInput
                            id="create-end_date"
                            v-model="createForm.end_date"
                            :type="createForm.all_day ? 'date' : 'datetime-local'"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="createForm.errors.end_date" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="create-location" value="Ubicación" />
                    <TextInput id="create-location" v-model="createForm.location" type="text" class="mt-1 block w-full" placeholder="Ej: Auditorio Principal" />
                    <InputError :message="createForm.errors.location" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-color" value="Color (opcional)" />
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                id="create-color"
                                v-model="createForm.color"
                                type="color"
                                class="h-9 w-14 rounded border border-gray-300 cursor-pointer"
                            />
                            <span class="text-xs text-gray-500">Dejar vacío para usar color del tipo</span>
                        </div>
                    </div>
                    <div>
                        <InputLabel for="create-period" value="Período Académico" />
                        <select
                            id="create-period"
                            v-model="createForm.academic_period_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Sin período</option>
                            <option v-for="period in periods" :key="period.id" :value="period.id">
                                {{ period.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <Checkbox id="create-notification" v-model:checked="createForm.send_notification" />
                        <InputLabel for="create-notification" value="Enviar notificación" class="ml-2" />
                    </div>
                    <div class="flex items-center">
                        <Checkbox id="create-status" v-model:checked="createForm.status" />
                        <InputLabel for="create-status" value="Activo" class="ml-2" />
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeCreatePanel" :disabled="createForm.processing">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitCreate" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creando...' : 'Crear Evento' }}
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Evento" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-title" value="Título" />
                    <TextInput id="edit-title" v-model="editForm.title" type="text" class="mt-1 block w-full" required />
                    <InputError :message="editForm.errors.title" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="edit-description" value="Descripción" />
                    <textarea
                        id="edit-description"
                        v-model="editForm.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    ></textarea>
                    <InputError :message="editForm.errors.description" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-type" value="Tipo" />
                        <select
                            id="edit-type"
                            v-model="editForm.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option v-for="type in typeOptions" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.type" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-visibility" value="Visibilidad" />
                        <select
                            id="edit-visibility"
                            v-model="editForm.visibility"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option v-for="vis in visibilityOptions" :key="vis.value" :value="vis.value">
                                {{ vis.label }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.visibility" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center mb-2">
                    <Checkbox id="edit-all_day" v-model:checked="editForm.all_day" />
                    <InputLabel for="edit-all_day" value="Todo el día" class="ml-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-start_date" value="Fecha Inicio" />
                        <TextInput
                            id="edit-start_date"
                            v-model="editForm.start_date"
                            :type="editForm.all_day ? 'date' : 'datetime-local'"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="editForm.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-end_date" value="Fecha Fin" />
                        <TextInput
                            id="edit-end_date"
                            v-model="editForm.end_date"
                            :type="editForm.all_day ? 'date' : 'datetime-local'"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="editForm.errors.end_date" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="edit-location" value="Ubicación" />
                    <TextInput id="edit-location" v-model="editForm.location" type="text" class="mt-1 block w-full" />
                    <InputError :message="editForm.errors.location" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-color" value="Color (opcional)" />
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                id="edit-color"
                                v-model="editForm.color"
                                type="color"
                                class="h-9 w-14 rounded border border-gray-300 cursor-pointer"
                            />
                            <span class="text-xs text-gray-500">Color personalizado</span>
                        </div>
                    </div>
                    <div>
                        <InputLabel for="edit-period" value="Período Académico" />
                        <select
                            id="edit-period"
                            v-model="editForm.academic_period_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Sin período</option>
                            <option v-for="period in periods" :key="period.id" :value="period.id">
                                {{ period.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <Checkbox id="edit-notification" v-model:checked="editForm.send_notification" />
                        <InputLabel for="edit-notification" value="Enviar notificación" class="ml-2" />
                    </div>
                    <div class="flex items-center">
                        <Checkbox id="edit-status" v-model:checked="editForm.status" />
                        <InputLabel for="edit-status" value="Activo" class="ml-2" />
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeEditPanel" :disabled="editForm.processing">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitEdit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Delete Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="mt-4 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Evento</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ selectedEvent?.title }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteEvent" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
