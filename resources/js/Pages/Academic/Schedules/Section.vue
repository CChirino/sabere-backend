<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';
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
import type { Schedule, Section, SubjectAssignment } from '@/types';

const props = defineProps<{
    sectionId: number;
}>();

const { get } = useApi();
const section = ref<Section | null>(null);
const scheduleData = ref<Record<string, Schedule[]>>({});
const assignments = ref<SubjectAssignment[]>([]);
const loading = ref(true);

// Panel states
const showCreatePanel = ref(false);
const showEditPanel = ref(false);
const showDeleteModal = ref(false);
const selectedSchedule = ref<Schedule | null>(null);
const selectedDay = ref('monday');
const deleting = ref(false);

// Forms
const form = useForm({
    subject_assignment_id: '',
    day_of_week: 'monday',
    start_time: '07:00',
    end_time: '08:00',
    classroom: '',
    notes: '',
    status: true,
});

const createForm = useForm({
    subject_assignment_id: '',
    day_of_week: 'monday',
    start_time: '07:00',
    end_time: '08:00',
    classroom: '',
    notes: '',
    status: true,
});

const days = [
    { key: 'monday', label: 'Lunes', short: 'Lun' },
    { key: 'tuesday', label: 'Martes', short: 'Mar' },
    { key: 'wednesday', label: 'Miércoles', short: 'Mié' },
    { key: 'thursday', label: 'Jueves', short: 'Jue' },
    { key: 'friday', label: 'Viernes', short: 'Vie' },
    { key: 'saturday', label: 'Sábado', short: 'Sáb' },
];

const timeSlots = [
    '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
    '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00'
];

// Calendar configuration
const calendarStartHour = 7; // 7:00 AM
const calendarEndHour = 15; // 3:00 PM
const pixelsPerHour = 80; // Height in pixels per hour (increased for better visibility)
const totalCalendarHeight = (calendarEndHour - calendarStartHour) * pixelsPerHour;

// Recreo configuration (reactive)
const recreo = ref({
    enabled: true,
    startTime: '10:00',
    endTime: '10:30',
    label: 'Recreo'
});

const showRecreoConfig = ref(false);

// Colors for subjects
const subjectColors = [
    'bg-blue-100 border-blue-400 text-blue-800',
    'bg-green-100 border-green-400 text-green-800',
    'bg-purple-100 border-purple-400 text-purple-800',
    'bg-yellow-100 border-yellow-400 text-yellow-800',
    'bg-pink-100 border-pink-400 text-pink-800',
    'bg-indigo-100 border-indigo-400 text-indigo-800',
    'bg-red-100 border-red-400 text-red-800',
    'bg-orange-100 border-orange-400 text-orange-800',
];

const getSubjectColor = (subjectId: number) => {
    return subjectColors[subjectId % subjectColors.length];
};

// Calculate position and height for a schedule block
const getScheduleStyle = (schedule: Schedule) => {
    const startMinutes = timeToMinutes(schedule.start_time);
    const endMinutes = timeToMinutes(schedule.end_time);
    const calendarStartMinutes = calendarStartHour * 60;
    
    const top = ((startMinutes - calendarStartMinutes) / 60) * pixelsPerHour;
    const height = ((endMinutes - startMinutes) / 60) * pixelsPerHour;
    
    return {
        top: `${top}px`,
        height: `${Math.max(height - 2, 20)}px`, // Min height of 20px, -2 for gap
    };
};

// Calculate recreo position
const getRecreoStyle = () => {
    const startMinutes = timeToMinutes(recreo.value.startTime);
    const endMinutes = timeToMinutes(recreo.value.endTime);
    const calendarStartMinutes = calendarStartHour * 60;
    
    const top = ((startMinutes - calendarStartMinutes) / 60) * pixelsPerHour;
    const height = ((endMinutes - startMinutes) / 60) * pixelsPerHour;
    
    return {
        top: `${top}px`,
        height: `${height}px`,
    };
};

// Time options for recreo config (7:00 to 15:00)
const recreoTimeOptions = computed(() => {
    const options = [];
    for (let h = calendarStartHour; h < calendarEndHour; h++) {
        options.push(`${h.toString().padStart(2, '0')}:00`);
        options.push(`${h.toString().padStart(2, '0')}:30`);
    }
    return options;
});

const timeToMinutes = (time: string): number => {
    if (!time) return 0;
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
};

// Generate hour labels for the sidebar
const hourLabels = computed(() => {
    const labels = [];
    for (let h = calendarStartHour; h <= calendarEndHour; h++) {
        labels.push({
            hour: h,
            label: `${h.toString().padStart(2, '0')}:00`,
            top: (h - calendarStartHour) * pixelsPerHour
        });
    }
    return labels;
});

const fetchSchedule = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/v1/schedules/by-section/${props.sectionId}`, {
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

const fetchAssignments = async () => {
    const data = await get<SubjectAssignment[]>(`/api/v1/subject-assignments?section_id=${props.sectionId}`);
    if (data) assignments.value = data;
};

const formatTime = (time: string) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
};

const formatTimeShort = (time: string) => {
    if (!time) return '';
    return time.substring(0, 5);
};

// Create
const openCreatePanel = (day: string) => {
    createForm.reset();
    createForm.day_of_week = day;
    createForm.status = true;
    showCreatePanel.value = true;
};

const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('academic.schedules.store'), {
        onSuccess: () => { closeCreatePanel(); fetchSchedule(); },
    });
};

// Edit
const editSchedule = (schedule: Schedule) => {
    selectedSchedule.value = schedule;
    form.subject_assignment_id = String(schedule.subject_assignment_id);
    form.day_of_week = schedule.day_of_week;
    form.start_time = formatTimeShort(schedule.start_time);
    form.end_time = formatTimeShort(schedule.end_time);
    form.classroom = schedule.classroom || '';
    form.notes = schedule.notes || '';
    form.status = schedule.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedSchedule.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedSchedule.value) return;
    form.put(route('academic.schedules.update', selectedSchedule.value.id), {
        onSuccess: () => { closeEditPanel(); fetchSchedule(); },
    });
};

// Delete
const confirmDelete = (schedule: Schedule) => {
    selectedSchedule.value = schedule;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedSchedule.value = null;
};

const deleteSchedule = () => {
    if (!selectedSchedule.value) return;
    deleting.value = true;
    router.delete(route('academic.schedules.destroy', selectedSchedule.value.id), {
        onSuccess: () => { closeDeleteModal(); fetchSchedule(); },
        onFinish: () => { deleting.value = false; },
    });
};

// Download functionality
const calendarRef = ref<HTMLElement | null>(null);
const downloading = ref(false);

const downloadAsPDF = async () => {
    if (!calendarRef.value) return;
    downloading.value = true;
    
    try {
        const canvas = await html2canvas(calendarRef.value, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
        });
        
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4',
        });
        
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const imgWidth = canvas.width;
        const imgHeight = canvas.height;
        const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
        const imgX = (pdfWidth - imgWidth * ratio) / 2;
        const imgY = 10;
        
        // Add title
        pdf.setFontSize(16);
        pdf.text(`Horario - ${section.value?.grade?.name} ${section.value?.name}`, pdfWidth / 2, 8, { align: 'center' });
        
        pdf.addImage(imgData, 'PNG', imgX, imgY + 5, imgWidth * ratio, imgHeight * ratio);
        pdf.save(`horario-${section.value?.grade?.name}-${section.value?.name}.pdf`);
    } catch (error) {
        console.error('Error generating PDF:', error);
    } finally {
        downloading.value = false;
    }
};

const downloadAsImage = async () => {
    if (!calendarRef.value) return;
    downloading.value = true;
    
    try {
        const canvas = await html2canvas(calendarRef.value, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
        });
        
        const link = document.createElement('a');
        link.download = `horario-${section.value?.grade?.name}-${section.value?.name}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (error) {
        console.error('Error generating image:', error);
    } finally {
        downloading.value = false;
    }
};

onMounted(() => {
    fetchSchedule();
    fetchAssignments();
});
</script>

<template>
    <Head :title="`Horario - ${section?.name || 'Sección'}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    Horario de Clases
                    <span v-if="section" class="text-gray-500 font-normal">
                        - {{ section.grade?.name }} {{ section.name }}
                    </span>
                </h1>
            </div>
        </template>

        <!-- Recreo Configuration -->
        <Card class="mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="recreo.enabled" 
                            class="rounded border-gray-300 text-sabere-accent focus:ring-sabere-accent"
                        />
                        <span class="ml-2 text-sm font-medium text-gray-700">Mostrar Recreo</span>
                    </label>
                    
                    <div v-if="recreo.enabled" class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">de</span>
                        <select 
                            v-model="recreo.startTime" 
                            class="text-sm rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                        >
                            <option v-for="time in recreoTimeOptions" :key="time" :value="time">{{ time }}</option>
                        </select>
                        <span class="text-sm text-gray-500">a</span>
                        <select 
                            v-model="recreo.endTime" 
                            class="text-sm rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                        >
                            <option v-for="time in recreoTimeOptions" :key="time" :value="time">{{ time }}</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 mr-2">Horario: 7:00 AM - 3:00 PM</span>
                    <div class="flex items-center space-x-1 border-l pl-3">
                        <button
                            @click="downloadAsPDF"
                            :disabled="downloading || loading"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sabere-accent disabled:opacity-50"
                            title="Descargar como PDF"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            PDF
                        </button>
                        <button
                            @click="downloadAsImage"
                            :disabled="downloading || loading"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sabere-accent disabled:opacity-50"
                            title="Descargar como imagen"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Imagen
                        </button>
                    </div>
                </div>
            </div>
        </Card>

        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando horario...</span>
            </div>
        </Card>

        <!-- Calendar Grid View -->
        <Card v-else :padding="false" class="overflow-hidden">
            <div ref="calendarRef" class="overflow-x-auto bg-white">
                <div class="min-w-[900px]">
                    <!-- Header -->
                    <div class="flex bg-gray-50 border-b border-gray-200">
                        <div class="w-16 flex-shrink-0 px-2 py-3 text-xs font-medium text-gray-500 uppercase">
                            Hora
                        </div>
                        <div 
                            v-for="day in days" 
                            :key="day.key" 
                            class="flex-1 px-2 py-3 text-center border-l border-gray-200"
                        >
                            <div class="flex flex-col items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">{{ day.label }}</span>
                                <button 
                                    @click="openCreatePanel(day.key)"
                                    class="mt-1 text-sabere-accent hover:text-sabere-dark transition-colors"
                                    title="Agregar clase"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calendar Body -->
                    <div class="flex">
                        <!-- Time Column -->
                        <div class="w-16 flex-shrink-0 bg-gray-50 border-r border-gray-200 relative" :style="{ height: totalCalendarHeight + 'px' }">
                            <div 
                                v-for="hour in hourLabels" 
                                :key="hour.hour"
                                class="absolute left-0 right-0 px-2 text-xs text-gray-500 -translate-y-1/2"
                                :style="{ top: hour.top + 'px' }"
                            >
                                {{ hour.label }}
                            </div>
                        </div>
                        
                        <!-- Day Columns -->
                        <div 
                            v-for="day in days" 
                            :key="day.key" 
                            class="flex-1 border-l border-gray-200 relative"
                            :style="{ height: totalCalendarHeight + 'px' }"
                        >
                            <!-- Hour grid lines -->
                            <div 
                                v-for="hour in hourLabels" 
                                :key="hour.hour"
                                class="absolute left-0 right-0 border-t border-gray-100"
                                :style="{ top: hour.top + 'px' }"
                            ></div>
                            
                            <!-- Recreo band -->
                            <div 
                                v-if="recreo.enabled"
                                class="absolute left-0 right-0 bg-amber-50 border-y border-amber-200 flex items-center justify-center z-0"
                                :style="getRecreoStyle()"
                            >
                                <span class="text-xs font-medium text-amber-600">{{ recreo.label }}</span>
                            </div>
                            
                            <!-- Schedule blocks -->
                            <div
                                v-for="schedule in scheduleData[day.key]"
                                :key="schedule.id"
                                :class="[
                                    'absolute left-1 right-1 rounded-lg border-l-4 p-2 cursor-pointer hover:shadow-lg transition-shadow overflow-hidden z-10',
                                    getSubjectColor(schedule.subject_assignment?.subject_id || 0)
                                ]"
                                :style="getScheduleStyle(schedule)"
                                @click="editSchedule(schedule)"
                            >
                                <div class="h-full flex flex-col">
                                    <p class="text-sm font-semibold truncate">
                                        {{ schedule.subject_assignment?.subject?.name }}
                                    </p>
                                    <p class="text-xs opacity-75 truncate">
                                        {{ schedule.subject_assignment?.teacher?.name }}
                                    </p>
                                    <div class="mt-auto pt-1 flex items-center justify-between text-xs">
                                        <span class="font-medium">
                                            {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                        </span>
                                        <span v-if="schedule.classroom" class="opacity-75 truncate ml-1">
                                            {{ schedule.classroom }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Legend -->
        <div v-if="!loading && assignments.length" class="mt-4">
            <Card>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Materias asignadas a esta sección</h3>
                <div class="flex flex-wrap gap-2">
                    <div 
                        v-for="assignment in assignments" 
                        :key="assignment.id"
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-medium border',
                            getSubjectColor(assignment.subject_id)
                        ]"
                    >
                        {{ assignment.subject?.name }} - {{ assignment.teacher?.name }}
                    </div>
                </div>
            </Card>
        </div>

        <!-- Create SlideOver -->
        <SlideOver :show="showCreatePanel" title="Agregar Clase al Horario" @close="closeCreatePanel">
            <!-- Warning if no assignments -->
            <div v-if="assignments.length === 0" class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800">Sin asignaciones</h4>
                        <p class="mt-1 text-sm text-yellow-700">
                            Esta sección no tiene materias asignadas. Primero debes ir a 
                            <a href="/academic/assignments" class="font-medium underline hover:text-yellow-900">Asignaciones</a> 
                            y asignar profesores y materias a esta sección.
                        </p>
                    </div>
                </div>
            </div>
            
            <form v-if="assignments.length > 0" @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-assignment" value="Materia y Profesor" />
                    <select id="create-assignment" v-model="createForm.subject_assignment_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar materia</option>
                        <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                            {{ assignment.subject?.name }} - {{ assignment.teacher?.name }}
                        </option>
                    </select>
                    <InputError :message="createForm.errors.subject_assignment_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-day" value="Día de la Semana" />
                    <select id="create-day" v-model="createForm.day_of_week" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option v-for="day in days" :key="day.key" :value="day.key">{{ day.label }}</option>
                    </select>
                    <InputError :message="createForm.errors.day_of_week" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-start" value="Hora Inicio" />
                        <select id="create-start" v-model="createForm.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                            <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
                        </select>
                        <InputError :message="createForm.errors.start_time" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-end" value="Hora Fin" />
                        <select id="create-end" v-model="createForm.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                            <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
                        </select>
                        <InputError :message="createForm.errors.end_time" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="create-classroom" value="Aula (opcional)" />
                    <TextInput id="create-classroom" v-model="createForm.classroom" type="text" class="mt-1 block w-full" placeholder="Ej: Aula 101" />
                    <InputError :message="createForm.errors.classroom" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-notes" value="Notas (opcional)" />
                    <textarea id="create-notes" v-model="createForm.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" placeholder="Observaciones"></textarea>
                    <InputError :message="createForm.errors.notes" class="mt-2" />
                </div>
                <div class="flex items-center">
                    <Checkbox id="create-status" v-model:checked="createForm.status" />
                    <InputLabel for="create-status" value="Activo" class="ml-2" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeCreatePanel" :disabled="createForm.processing">Cancelar</SecondaryButton>
                    <PrimaryButton v-if="assignments.length > 0" @click="submitCreate" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Guardando...' : 'Agregar al Horario' }}
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Clase" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-assignment" value="Materia y Profesor" />
                    <select id="edit-assignment" v-model="form.subject_assignment_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar materia</option>
                        <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                            {{ assignment.subject?.name }} - {{ assignment.teacher?.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.subject_assignment_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-day" value="Día de la Semana" />
                    <select id="edit-day" v-model="form.day_of_week" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option v-for="day in days" :key="day.key" :value="day.key">{{ day.label }}</option>
                    </select>
                    <InputError :message="form.errors.day_of_week" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-start" value="Hora Inicio" />
                        <select id="edit-start" v-model="form.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                            <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
                        </select>
                        <InputError :message="form.errors.start_time" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-end" value="Hora Fin" />
                        <select id="edit-end" v-model="form.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                            <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
                        </select>
                        <InputError :message="form.errors.end_time" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="edit-classroom" value="Aula (opcional)" />
                    <TextInput id="edit-classroom" v-model="form.classroom" type="text" class="mt-1 block w-full" placeholder="Ej: Aula 101" />
                    <InputError :message="form.errors.classroom" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-notes" value="Notas (opcional)" />
                    <textarea id="edit-notes" v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" placeholder="Observaciones"></textarea>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>
                <div class="flex items-center">
                    <Checkbox id="edit-status" v-model:checked="form.status" />
                    <InputLabel for="edit-status" value="Activo" class="ml-2" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <DangerButton @click="closeEditPanel(); confirmDelete(selectedSchedule!)" :disabled="form.processing">
                        Eliminar
                    </DangerButton>
                    <div class="flex space-x-2">
                        <SecondaryButton @click="closeEditPanel" :disabled="form.processing">Cancelar</SecondaryButton>
                        <PrimaryButton @click="submitEdit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </PrimaryButton>
                    </div>
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar del Horario</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ selectedSchedule?.subject_assignment?.subject?.name }}</strong> del horario?
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteSchedule" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
