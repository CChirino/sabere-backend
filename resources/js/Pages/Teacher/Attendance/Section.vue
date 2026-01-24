<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps<{
    sectionId: number;
}>();

interface Student {
    id: number;
    name: string;
    email: string;
    attendance_id: number | null;
    status: 'present' | 'absent' | 'late' | 'excused' | null;
    notes: string;
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

const section = ref<Section | null>(null);
const assignments = ref<SubjectAssignment[]>([]);
const students = ref<Student[]>([]);
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const selectedAssignmentId = ref<number | null>(null);
const loading = ref(true);
const saving = ref(false);
const successMessage = ref('');

const statusOptions = [
    { value: 'present', label: 'Presente', color: 'bg-green-500', icon: '✓' },
    { value: 'absent', label: 'Ausente', color: 'bg-red-500', icon: '✗' },
    { value: 'late', label: 'Tardanza', color: 'bg-yellow-500', icon: '⏰' },
    { value: 'excused', label: 'Justificado', color: 'bg-blue-500', icon: '📋' },
];

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

const fetchAttendance = async () => {
    loading.value = true;
    try {
        let url = `/api/v1/attendance/section/${props.sectionId}?date=${selectedDate.value}`;
        if (selectedAssignmentId.value) {
            url += `&subject_assignment_id=${selectedAssignmentId.value}`;
        }
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        students.value = data.students || [];
    } catch (error) {
        console.error('Error fetching attendance:', error);
    } finally {
        loading.value = false;
    }
};

const setStatus = (studentId: number, status: 'present' | 'absent' | 'late' | 'excused') => {
    const student = students.value.find(s => s.id === studentId);
    if (student) {
        student.status = status;
    }
};

const setAllStatus = (status: 'present' | 'absent' | 'late' | 'excused') => {
    students.value.forEach(student => {
        student.status = status;
    });
};

const saveAttendance = async () => {
    saving.value = true;
    successMessage.value = '';
    
    try {
        const attendances = students.value
            .filter(s => s.status !== null)
            .map(s => ({
                student_id: s.id,
                status: s.status,
                notes: s.notes || null,
            }));

        if (attendances.length === 0) {
            alert('Por favor, registra la asistencia de al menos un estudiante.');
            saving.value = false;
            return;
        }

        const response = await fetch('/teacher/attendance', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
            },
            credentials: 'include',
            body: JSON.stringify({
                section_id: props.sectionId,
                subject_assignment_id: selectedAssignmentId.value,
                date: selectedDate.value,
                attendances,
            }),
        });

        if (response.ok) {
            successMessage.value = 'Asistencia guardada correctamente';
            setTimeout(() => {
                successMessage.value = '';
            }, 3000);
        } else {
            const data = await response.json();
            alert(data.message || 'Error al guardar la asistencia');
        }
    } catch (error) {
        console.error('Error saving attendance:', error);
        alert('Error de conexión');
    } finally {
        saving.value = false;
    }
};

const getStatusColor = (status: string | null) => {
    switch (status) {
        case 'present': return 'bg-green-100 text-green-800 border-green-300';
        case 'absent': return 'bg-red-100 text-red-800 border-red-300';
        case 'late': return 'bg-yellow-100 text-yellow-800 border-yellow-300';
        case 'excused': return 'bg-blue-100 text-blue-800 border-blue-300';
        default: return 'bg-gray-100 text-gray-500 border-gray-300';
    }
};

const attendanceSummary = computed(() => {
    const total = students.value.length;
    const present = students.value.filter(s => s.status === 'present').length;
    const absent = students.value.filter(s => s.status === 'absent').length;
    const late = students.value.filter(s => s.status === 'late').length;
    const excused = students.value.filter(s => s.status === 'excused').length;
    const pending = students.value.filter(s => s.status === null).length;
    
    return { total, present, absent, late, excused, pending };
});

watch([selectedDate, selectedAssignmentId], () => {
    fetchAttendance();
});

onMounted(async () => {
    await Promise.all([fetchSection(), fetchAssignments()]);
    await fetchAttendance();
});
</script>

<template>
    <Head :title="`Asistencia - ${section?.grade?.name || ''} ${section?.name || ''}`" />

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
                            Tomar Asistencia
                        </h1>
                        <p v-if="section" class="text-sm text-gray-600 truncate">
                            {{ section.grade?.name }} {{ section.name }}
                        </p>
                    </div>
                </div>
                <Link 
                    :href="route('teacher.attendance.report', { id: sectionId })"
                    class="hidden sm:inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Ver Reporte
                </Link>
            </div>
        </template>

        <!-- Success Message -->
        <div v-if="successMessage" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-green-800">{{ successMessage }}</span>
        </div>

        <!-- Filters -->
        <Card class="mb-4">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input 
                        type="date" 
                        v-model="selectedDate"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-sabere-primary focus:border-sabere-primary"
                    />
                </div>
                <div v-if="assignments.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Materia (opcional)</label>
                    <select 
                        v-model="selectedAssignmentId"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-sabere-primary focus:border-sabere-primary min-w-[200px]"
                    >
                        <option :value="null">Asistencia general</option>
                        <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                            {{ assignment.subject.name }}
                        </option>
                    </select>
                </div>
                <div class="flex-1"></div>
                <!-- Quick actions -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Marcar todos:</span>
                    <button 
                        v-for="option in statusOptions"
                        :key="option.value"
                        @click="setAllStatus(option.value as any)"
                        :class="[option.color, 'w-8 h-8 rounded-full text-white text-sm font-bold hover:opacity-80 transition-opacity']"
                        :title="option.label"
                    >
                        {{ option.icon }}
                    </button>
                </div>
            </div>
        </Card>

        <!-- Summary -->
        <Card class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-gray-900">{{ attendanceSummary.total }}</span>
                        <span class="text-sm text-gray-500">Total</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-green-600">{{ attendanceSummary.present }}</span>
                        <span class="text-sm text-gray-500">Presentes</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-red-600">{{ attendanceSummary.absent }}</span>
                        <span class="text-sm text-gray-500">Ausentes</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-yellow-600">{{ attendanceSummary.late }}</span>
                        <span class="text-sm text-gray-500">Tardanzas</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-blue-600">{{ attendanceSummary.excused }}</span>
                        <span class="text-sm text-gray-500">Justificados</span>
                    </div>
                    <div v-if="attendanceSummary.pending > 0" class="text-center">
                        <span class="block text-2xl font-bold text-gray-400">{{ attendanceSummary.pending }}</span>
                        <span class="text-sm text-gray-500">Pendientes</span>
                    </div>
                </div>
                <PrimaryButton 
                    @click="saveAttendance" 
                    :disabled="saving || attendanceSummary.pending === attendanceSummary.total"
                    class="px-6"
                >
                    <svg v-if="saving" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ saving ? 'Guardando...' : 'Guardar Asistencia' }}
                </PrimaryButton>
            </div>
        </Card>

        <!-- Loading -->
        <Card v-if="loading" class="text-center py-12">
            <div class="flex items-center justify-center">
                <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-500">Cargando estudiantes...</span>
            </div>
        </Card>

        <!-- Students List -->
        <Card v-else-if="students.length > 0" :padding="false">
            <div class="divide-y divide-gray-200">
                <div 
                    v-for="student in students" 
                    :key="student.id"
                    :class="[
                        'p-4 flex items-center justify-between gap-4 transition-colors',
                        student.status ? getStatusColor(student.status) : 'bg-white hover:bg-gray-50'
                    ]"
                >
                    <div class="flex items-center min-w-0">
                        <div class="flex-shrink-0 h-10 w-10 bg-sabere-primary/10 rounded-full flex items-center justify-center">
                            <span class="text-sabere-primary font-medium">
                                {{ student.name?.charAt(0) }}
                            </span>
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ student.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ student.email }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            @click="setStatus(student.id, option.value as any)"
                            :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center text-lg transition-all',
                                student.status === option.value 
                                    ? `${option.color} text-white shadow-lg scale-110` 
                                    : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                            ]"
                            :title="option.label"
                        >
                            {{ option.icon }}
                        </button>
                    </div>
                </div>
            </div>
        </Card>

        <!-- No students -->
        <Card v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin estudiantes</h3>
            <p class="mt-1 text-gray-500">No hay estudiantes inscritos en esta sección.</p>
        </Card>

        <!-- Legend -->
        <Card class="mt-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Leyenda</h3>
            <div class="flex flex-wrap gap-4 text-sm">
                <div v-for="option in statusOptions" :key="option.value" class="flex items-center">
                    <span :class="[option.color, 'w-6 h-6 rounded-full text-white flex items-center justify-center text-xs mr-2']">
                        {{ option.icon }}
                    </span>
                    <span class="text-gray-600">{{ option.label }}</span>
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
