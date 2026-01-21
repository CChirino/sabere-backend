<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import type { SubjectAssignment, Term, Task, User, Enrollment, TaskSubmission } from '@/types';

const props = defineProps<{
    assignmentId: number;
}>();

interface ManualScore {
    id: number;
    student_id: number;
    title: string;
    description: string | null;
    score: number;
    max_score: number;
    graded_at: string;
}

interface StudentGrade {
    student: User;
    enrollment: Enrollment;
    tasks: {
        task: Task;
        submission: TaskSubmission | null;
        score: number | null;
        maxScore: number;
    }[];
    manualScores: ManualScore[];
    totalScore: number;
    totalMaxScore: number;
    percentage: number;
    finalGrade: number;
}

const assignment = ref<SubjectAssignment | null>(null);
const terms = ref<Term[]>([]);
const tasks = ref<Task[]>([]);
const students = ref<StudentGrade[]>([]);
const manualScores = ref<ManualScore[]>([]);
const selectedTermId = ref<number | null>(null);
const loading = ref(true);

// Modal states for manual scores
const showAddManualScoreModal = ref(false);
const showEditManualScoreModal = ref(false);
const showDeleteManualScoreModal = ref(false);
const selectedStudentForManual = ref<User | null>(null);
const selectedManualScore = ref<ManualScore | null>(null);
const manualScoreForm = ref({
    title: '',
    description: '',
    score: '',
    max_score: '20',
});
const manualScoreErrors = ref<Record<string, string>>({});
const savingManualScore = ref(false);
const deletingManualScore = ref(false);

const selectedTerm = computed(() => {
    return terms.value.find(t => t.id === selectedTermId.value) || null;
});

const fetchAssignment = async () => {
    try {
        const response = await fetch(`/api/v1/subject-assignments/${props.assignmentId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        assignment.value = data.data;
        console.log('Assignment loaded:', assignment.value);
    } catch (error) {
        console.error('Error fetching assignment:', error);
    }
};

const fetchTerms = async () => {
    if (!assignment.value) return;
    try {
        // Intentar obtener términos del período académico de la asignación
        let url = `/api/v1/terms`;
        if (assignment.value.academic_period_id) {
            url += `?academic_period_id=${assignment.value.academic_period_id}`;
        }
        
        console.log('Fetching terms from:', url);
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        terms.value = data.data || [];
        
        console.log('Terms loaded:', terms.value.length, terms.value);
        
        // Seleccionar el primer lapso automáticamente
        if (terms.value.length > 0 && !selectedTermId.value) {
            selectedTermId.value = terms.value[0].id;
            console.log('Selected term:', selectedTermId.value);
        }
    } catch (error) {
        console.error('Error fetching terms:', error);
    }
};

const fetchTasksAndGrades = async () => {
    if (!assignment.value || !selectedTermId.value) {
        loading.value = false;
        return;
    }
    loading.value = true;
    
    try {
        // 1. Fetch tasks for this assignment and term
        const tasksResponse = await fetch(`/api/v1/tasks?subject_assignment_id=${props.assignmentId}&term_id=${selectedTermId.value}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'include',
        });
        const tasksData = await tasksResponse.json();
        tasks.value = tasksData.data || [];

        // 2. Fetch enrolled students
        const enrollmentsResponse = await fetch(`/api/v1/enrollments?section_id=${assignment.value.section_id}&status=active`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'include',
        });
        const enrollmentsData = await enrollmentsResponse.json();
        const enrollments = enrollmentsData.data || [];

        // 3. Fetch all submissions for these tasks
        const submissions: TaskSubmission[] = [];
        for (const task of tasks.value) {
            const subResponse = await fetch(`/api/v1/task-submissions?task_id=${task.id}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'include',
            });
            const subData = await subResponse.json();
            submissions.push(...(subData.data || []));
        }

        // 4. Fetch manual scores for this assignment and term
        try {
            const manualResponse = await fetch(`/api/v1/manual-scores?subject_assignment_id=${props.assignmentId}&term_id=${selectedTermId.value}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'include',
            });
            if (manualResponse.ok) {
                const manualData = await manualResponse.json();
                manualScores.value = manualData.data || [];
            } else {
                manualScores.value = [];
            }
        } catch (e) {
            console.warn('Manual scores not available:', e);
            manualScores.value = [];
        }

        // 5. Calculate grades for each student (including manual scores)
        students.value = enrollments.map((enrollment: Enrollment) => {
            const studentTasks = tasks.value.map(task => {
                const submission = submissions.find(s => s.task_id === task.id && s.student_id === enrollment.student_id);
                return { task, submission: submission || null, score: submission?.score ?? null, maxScore: task.max_score };
            });

            // Get manual scores for this student
            const studentManualScores = manualScores.value.filter(ms => ms.student_id === enrollment.student_id);

            // Calculate totals from tasks
            const gradedTasks = studentTasks.filter(t => t.score !== null);
            const tasksTotalScore = gradedTasks.reduce((sum, t) => sum + Number(t.score || 0), 0);
            const tasksTotalMaxScore = gradedTasks.reduce((sum, t) => sum + Number(t.maxScore || 0), 0);

            // Calculate totals from manual scores
            const manualTotalScore = studentManualScores.reduce((sum, ms) => sum + Number(ms.score || 0), 0);
            const manualTotalMaxScore = studentManualScores.reduce((sum, ms) => sum + Number(ms.max_score || 0), 0);

            // Combined totals
            const totalScore = Number(tasksTotalScore) + Number(manualTotalScore);
            const totalMaxScore = Number(tasksTotalMaxScore) + Number(manualTotalMaxScore);
            const percentage = totalMaxScore > 0 ? (totalScore / totalMaxScore) * 100 : 0;
            const finalGrade = (percentage / 100) * 20;

            return {
                student: enrollment.student,
                enrollment,
                tasks: studentTasks,
                manualScores: studentManualScores,
                totalScore,
                totalMaxScore,
                percentage,
                finalGrade: Math.round(finalGrade * 100) / 100,
            };
        });
    } catch (error) {
        console.error('Error fetching tasks and grades:', error);
    } finally {
        loading.value = false;
    }
};

const getGradeColor = (grade: number) => {
    if (grade >= 18) return 'text-green-600';
    if (grade >= 15) return 'text-blue-600';
    if (grade >= 10) return 'text-yellow-600';
    return 'text-red-600';
};

const getLetterGrade = (grade: number) => {
    if (grade >= 18) return 'A';
    if (grade >= 15) return 'B';
    if (grade >= 12) return 'C';
    if (grade >= 10) return 'D';
    return 'E';
};

const getBadgeColor = (grade: number): 'green' | 'blue' | 'yellow' | 'red' => {
    if (grade >= 18) return 'green';
    if (grade >= 15) return 'blue';
    if (grade >= 10) return 'yellow';
    return 'red';
};

const classAverage = computed(() => {
    if (students.value.length === 0) return null;
    const sum = students.value.reduce((acc, s) => acc + s.finalGrade, 0);
    return (sum / students.value.length).toFixed(2);
});

const passCount = computed(() => {
    return students.value.filter(s => s.finalGrade >= 10).length;
});

const failCount = computed(() => {
    return students.value.filter(s => s.finalGrade < 10 && s.totalMaxScore > 0).length;
});

// Manual score functions
const openAddManualScoreModal = (student: User) => {
    selectedStudentForManual.value = student;
    manualScoreForm.value = {
        title: '',
        description: '',
        score: '',
        max_score: '20',
    };
    manualScoreErrors.value = {};
    showAddManualScoreModal.value = true;
};

const openEditManualScoreModal = (manualScore: ManualScore, student: User) => {
    selectedStudentForManual.value = student;
    selectedManualScore.value = manualScore;
    manualScoreForm.value = {
        title: manualScore.title,
        description: manualScore.description || '',
        score: String(manualScore.score),
        max_score: String(manualScore.max_score),
    };
    manualScoreErrors.value = {};
    showEditManualScoreModal.value = true;
};

const openDeleteManualScoreModal = (manualScore: ManualScore) => {
    selectedManualScore.value = manualScore;
    showDeleteManualScoreModal.value = true;
};

const closeManualScoreModals = () => {
    showAddManualScoreModal.value = false;
    showEditManualScoreModal.value = false;
    showDeleteManualScoreModal.value = false;
    selectedStudentForManual.value = null;
    selectedManualScore.value = null;
    manualScoreErrors.value = {};
};

const saveManualScore = async () => {
    if (!selectedStudentForManual.value || !selectedTermId.value) return;
    
    manualScoreErrors.value = {};
    
    // Validate
    if (!manualScoreForm.value.title.trim()) {
        manualScoreErrors.value.title = 'El título es requerido';
        return;
    }
    if (!manualScoreForm.value.score || isNaN(Number(manualScoreForm.value.score))) {
        manualScoreErrors.value.score = 'La nota es requerida';
        return;
    }
    if (Number(manualScoreForm.value.score) > Number(manualScoreForm.value.max_score)) {
        manualScoreErrors.value.score = 'La nota no puede ser mayor al máximo';
        return;
    }

    savingManualScore.value = true;
    
    try {
        const response = await fetch('/api/v1/manual-scores', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
            },
            credentials: 'include',
            body: JSON.stringify({
                student_id: selectedStudentForManual.value.id,
                subject_assignment_id: props.assignmentId,
                term_id: selectedTermId.value,
                title: manualScoreForm.value.title,
                description: manualScoreForm.value.description || null,
                score: Number(manualScoreForm.value.score),
                max_score: Number(manualScoreForm.value.max_score),
            }),
        });

        if (response.ok) {
            closeManualScoreModals();
            await fetchTasksAndGrades();
        } else {
            const data = await response.json();
            manualScoreErrors.value.general = data.message || 'Error al guardar la nota';
        }
    } catch (error) {
        console.error('Error saving manual score:', error);
        manualScoreErrors.value.general = 'Error de conexión';
    } finally {
        savingManualScore.value = false;
    }
};

const updateManualScore = async () => {
    if (!selectedManualScore.value) return;
    
    manualScoreErrors.value = {};
    
    // Validate
    if (!manualScoreForm.value.title.trim()) {
        manualScoreErrors.value.title = 'El título es requerido';
        return;
    }
    if (!manualScoreForm.value.score || isNaN(Number(manualScoreForm.value.score))) {
        manualScoreErrors.value.score = 'La nota es requerida';
        return;
    }
    if (Number(manualScoreForm.value.score) > Number(manualScoreForm.value.max_score)) {
        manualScoreErrors.value.score = 'La nota no puede ser mayor al máximo';
        return;
    }

    savingManualScore.value = true;
    
    try {
        const response = await fetch(`/api/v1/manual-scores/${selectedManualScore.value.id}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
            },
            credentials: 'include',
            body: JSON.stringify({
                title: manualScoreForm.value.title,
                description: manualScoreForm.value.description || null,
                score: Number(manualScoreForm.value.score),
                max_score: Number(manualScoreForm.value.max_score),
            }),
        });

        if (response.ok) {
            closeManualScoreModals();
            await fetchTasksAndGrades();
        } else {
            const data = await response.json();
            manualScoreErrors.value.general = data.message || 'Error al actualizar la nota';
        }
    } catch (error) {
        console.error('Error updating manual score:', error);
        manualScoreErrors.value.general = 'Error de conexión';
    } finally {
        savingManualScore.value = false;
    }
};

const deleteManualScore = async () => {
    if (!selectedManualScore.value) return;
    
    deletingManualScore.value = true;
    
    try {
        const response = await fetch(`/api/v1/manual-scores/${selectedManualScore.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
            },
            credentials: 'include',
        });

        if (response.ok) {
            closeManualScoreModals();
            await fetchTasksAndGrades();
        }
    } catch (error) {
        console.error('Error deleting manual score:', error);
    } finally {
        deletingManualScore.value = false;
    }
};

// Get unique manual score titles for column headers
const uniqueManualScoreTitles = computed(() => {
    const titles = new Map<string, { title: string; maxScore: number }>();
    manualScores.value.forEach(ms => {
        if (!titles.has(ms.title)) {
            titles.set(ms.title, { title: ms.title, maxScore: ms.max_score });
        }
    });
    return Array.from(titles.values());
});

watch(selectedTermId, () => {
    if (selectedTermId.value) {
        fetchTasksAndGrades();
    }
});

onMounted(async () => {
    await fetchAssignment();
    await fetchTerms();
    await fetchTasksAndGrades();
});
</script>

<template>
    <Head :title="`Boleta - ${assignment?.subject?.name || 'Materia'}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <Link :href="route('teacher.scores.index')" class="text-gray-500 hover:text-gray-700 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="min-w-0">
                        <h1 class="text-xl font-bold text-gray-900 truncate">
                            Boleta de Calificaciones
                        </h1>
                        <p v-if="assignment" class="text-sm text-gray-600 truncate">
                            {{ assignment.subject?.name }} - {{ assignment.section?.grade?.name }} {{ assignment.section?.name }}
                        </p>
                    </div>
                </div>
                <Link :href="route('teacher.tasks.index')" class="hidden sm:inline-flex items-center px-4 py-2 bg-sabere-primary text-white rounded-lg text-sm font-medium hover:bg-sabere-dark transition-colors flex-shrink-0">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Gestionar Evaluaciones
                </Link>
            </div>
        </template>

        <!-- Term Selector -->
        <Card class="mb-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <InputLabel value="Lapso:" class="font-medium" />
                    <div v-if="terms.length > 0" class="flex space-x-2">
                        <button
                            v-for="term in terms"
                            :key="term.id"
                            @click="selectedTermId = term.id"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                                selectedTermId === term.id
                                    ? 'bg-sabere-dark text-white shadow-md'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ term.name }}
                        </button>
                    </div>
                    <span v-else class="text-sm text-amber-600 bg-amber-50 px-3 py-1 rounded-lg">
                        No hay lapsos configurados para este período
                    </span>
                </div>

                <!-- Stats -->
                <div v-if="!loading && students.length > 0" class="flex items-center space-x-6 text-sm">
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-sabere-primary">{{ classAverage || '-' }}</span>
                        <span class="text-gray-500">Promedio</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-green-600">{{ passCount }}</span>
                        <span class="text-gray-500">Aprobados</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-red-600">{{ failCount }}</span>
                        <span class="text-gray-500">Reprobados</span>
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
                <span class="ml-2 text-gray-500">Cargando estudiantes...</span>
            </div>
        </Card>

        <!-- No terms warning -->
        <Card v-else-if="terms.length === 0" class="text-center py-12 border-amber-200 bg-amber-50">
            <svg class="mx-auto h-12 w-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-amber-800">Sin lapsos configurados</h3>
            <p class="mt-1 text-amber-600">No hay lapsos configurados para este período académico.</p>
            <p class="mt-2 text-sm text-gray-500">Contacta al administrador para configurar los lapsos del período.</p>
        </Card>

        <!-- No students -->
        <Card v-else-if="students.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sin estudiantes</h3>
            <p class="mt-1 text-gray-500">No hay estudiantes inscritos en esta sección.</p>
        </Card>

        <!-- Info + Grades Table -->
        <template v-else>
            <!-- Info about how grades work -->
            <Card class="mb-4 bg-blue-50 border-blue-200">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p><strong>Las notas se calculan automáticamente</strong> basándose en las evaluaciones calificadas + notas manuales.</p>
                            <p class="mt-1 text-blue-600">Puedes agregar notas manuales (participación, exámenes, etc.) haciendo clic en el botón <strong>+</strong> de cada estudiante.</p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Grades Table based on Tasks -->
            <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                                Estudiante
                            </th>
                            <th 
                                v-for="task in tasks" 
                                :key="task.id"
                                class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]"
                            >
                                <div class="truncate max-w-[100px]" :title="task.title">{{ task.title }}</div>
                                <div class="text-gray-400 font-normal normal-case">({{ task.max_score }} pts)</div>
                            </th>
                            <!-- Manual scores columns -->
                            <th 
                                v-for="manualTitle in uniqueManualScoreTitles" 
                                :key="manualTitle.title"
                                class="px-3 py-3 text-center text-xs font-medium text-purple-600 uppercase tracking-wider min-w-[100px] bg-purple-50"
                            >
                                <div class="truncate max-w-[100px]" :title="manualTitle.title">{{ manualTitle.title }}</div>
                                <div class="text-purple-400 font-normal normal-case">({{ manualTitle.maxScore }} pts)</div>
                            </th>
                            <!-- Add manual score column -->
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider bg-gray-100 min-w-[50px]">
                                <span class="sr-only">Agregar</span>
                                +
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-sabere-primary/5">
                                Total
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-sabere-primary/10">
                                Nota Final
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in students" :key="item.student.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white z-10">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 bg-sabere-primary/10 rounded-full flex items-center justify-center">
                                        <span class="text-sabere-primary text-sm font-medium">
                                            {{ item.student.name?.charAt(0) }}
                                        </span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ item.student.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td 
                                v-for="taskGrade in item.tasks" 
                                :key="taskGrade.task.id"
                                class="px-3 py-3 whitespace-nowrap text-center"
                            >
                                <span v-if="taskGrade.score !== null" :class="[
                                    'font-medium',
                                    taskGrade.score >= taskGrade.maxScore * 0.5 ? 'text-green-600' : 'text-red-600'
                                ]">
                                    {{ taskGrade.score }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <!-- Manual scores cells -->
                            <td 
                                v-for="manualTitle in uniqueManualScoreTitles" 
                                :key="manualTitle.title"
                                class="px-3 py-3 whitespace-nowrap text-center bg-purple-50/50"
                            >
                                <template v-for="ms in item.manualScores.filter(m => m.title === manualTitle.title)" :key="ms.id">
                                    <button 
                                        @click="openEditManualScoreModal(ms, item.student)"
                                        class="font-medium text-purple-600 hover:text-purple-800 hover:underline cursor-pointer"
                                        :title="`Editar: ${ms.description || ms.title}`"
                                    >
                                        {{ ms.score }}
                                    </button>
                                </template>
                                <span v-if="!item.manualScores.find(m => m.title === manualTitle.title)" class="text-gray-300">-</span>
                            </td>
                            <!-- Add manual score button -->
                            <td class="px-2 py-3 whitespace-nowrap text-center bg-gray-50">
                                <button 
                                    @click="openAddManualScoreModal(item.student)"
                                    class="w-7 h-7 rounded-full bg-purple-100 text-purple-600 hover:bg-purple-200 transition-colors inline-flex items-center justify-center"
                                    title="Agregar nota manual"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center bg-sabere-primary/5">
                                <span class="text-sm text-gray-700">
                                    {{ Number(item.totalScore || 0).toFixed(2) }} / {{ Number(item.totalMaxScore || 0).toFixed(2) }}
                                </span>
                                <div class="text-xs text-gray-500">
                                    ({{ Number(item.percentage || 0).toFixed(1) }}%)
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center bg-sabere-primary/10">
                                <div class="flex items-center justify-center space-x-2">
                                    <span :class="['text-xl font-bold', getGradeColor(item.finalGrade)]">
                                        {{ Number(item.finalGrade || 0).toFixed(1) }}
                                    </span>
                                    <Badge :color="getBadgeColor(item.finalGrade)" size="sm">
                                        {{ getLetterGrade(item.finalGrade) }}
                                    </Badge>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </Card>
        </template>

        <!-- Legend -->
        <Card class="mt-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Escala de Calificaciones</h3>
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center">
                    <span class="w-6 h-6 rounded-full bg-green-100 text-green-800 flex items-center justify-center text-xs font-bold mr-2">A</span>
                    <span class="text-gray-600">18-20 (Excelente)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold mr-2">B</span>
                    <span class="text-gray-600">15-17 (Bueno)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-800 flex items-center justify-center text-xs font-bold mr-2">C</span>
                    <span class="text-gray-600">12-14 (Regular)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-800 flex items-center justify-center text-xs font-bold mr-2">D</span>
                    <span class="text-gray-600">10-11 (Suficiente)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-6 h-6 rounded-full bg-red-100 text-red-800 flex items-center justify-center text-xs font-bold mr-2">E</span>
                    <span class="text-gray-600">0-9 (Reprobado)</span>
                </div>
            </div>
        </Card>

        <!-- Add Manual Score Modal -->
        <Modal :show="showAddManualScoreModal" @close="closeManualScoreModals" max-width="md">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Agregar Nota Manual</h3>
                        <p class="text-sm text-gray-500">{{ selectedStudentForManual?.name }}</p>
                    </div>
                </div>

                <div v-if="manualScoreErrors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                    {{ manualScoreErrors.general }}
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="manual-title" value="Título *" />
                        <TextInput
                            id="manual-title"
                            v-model="manualScoreForm.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej: Participación, Examen Parcial, Exposición"
                        />
                        <InputError :message="manualScoreErrors.title" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="manual-description" value="Descripción (opcional)" />
                        <textarea
                            id="manual-description"
                            v-model="manualScoreForm.description"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="2"
                            placeholder="Detalles adicionales..."
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="manual-score" value="Nota obtenida *" />
                            <TextInput
                                id="manual-score"
                                v-model="manualScoreForm.score"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                placeholder="0.00"
                            />
                            <InputError :message="manualScoreErrors.score" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="manual-max-score" value="Nota máxima *" />
                            <TextInput
                                id="manual-max-score"
                                v-model="manualScoreForm.max_score"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="mt-1 block w-full"
                                placeholder="20"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeManualScoreModals" :disabled="savingManualScore">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="saveManualScore" :disabled="savingManualScore">
                        <span v-if="savingManualScore">Guardando...</span>
                        <span v-else>Guardar Nota</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Edit Manual Score Modal -->
        <Modal :show="showEditManualScoreModal" @close="closeManualScoreModals" max-width="md">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Editar Nota Manual</h3>
                        <p class="text-sm text-gray-500">{{ selectedStudentForManual?.name }}</p>
                    </div>
                </div>

                <div v-if="manualScoreErrors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                    {{ manualScoreErrors.general }}
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="edit-manual-title" value="Título *" />
                        <TextInput
                            id="edit-manual-title"
                            v-model="manualScoreForm.title"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="manualScoreErrors.title" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="edit-manual-description" value="Descripción (opcional)" />
                        <textarea
                            id="edit-manual-description"
                            v-model="manualScoreForm.description"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="2"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="edit-manual-score" value="Nota obtenida *" />
                            <TextInput
                                id="edit-manual-score"
                                v-model="manualScoreForm.score"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="manualScoreErrors.score" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="edit-manual-max-score" value="Nota máxima *" />
                            <TextInput
                                id="edit-manual-max-score"
                                v-model="manualScoreForm.max_score"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="mt-1 block w-full"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <DangerButton @click="openDeleteManualScoreModal(selectedManualScore!)" :disabled="savingManualScore">
                        Eliminar
                    </DangerButton>
                    <div class="flex space-x-3">
                        <SecondaryButton @click="closeManualScoreModals" :disabled="savingManualScore">
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton @click="updateManualScore" :disabled="savingManualScore">
                            <span v-if="savingManualScore">Guardando...</span>
                            <span v-else>Actualizar</span>
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Manual Score Modal -->
        <Modal :show="showDeleteManualScoreModal" @close="closeManualScoreModals" max-width="sm">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <div class="mt-4 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Nota Manual</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar la nota <strong>"{{ selectedManualScore?.title }}"</strong>?
                        Esta acción no se puede deshacer.
                    </p>
                </div>

                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeManualScoreModals" :disabled="deletingManualScore">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton @click="deleteManualScore" :disabled="deletingManualScore">
                        <span v-if="deletingManualScore">Eliminando...</span>
                        <span v-else>Eliminar</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
