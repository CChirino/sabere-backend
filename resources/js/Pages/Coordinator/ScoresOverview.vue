<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';

interface SectionScore {
    section_id: number;
    section_name: string;
    grade_name: string;
    subject_name: string;
    teacher_name: string;
    students_count: number;
    scores_entered: number;
    average_score: number;
    term_name: string;
}

interface Stats {
    total_sections: number;
    sections_with_scores: number;
    average_score: number;
    students_below_passing: number;
}

const sections = ref<SectionScore[]>([]);
const stats = ref<Stats>({ total_sections: 0, sections_with_scores: 0, average_score: 0, students_below_passing: 0 });
const loading = ref(true);
const selectedTerm = ref('');
const terms = ref<{ id: number; name: string }[]>([]);
const pagination = ref<{
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
} | null>(null);

const columns = [
    { key: 'section_name', label: 'Sección' },
    { key: 'grade_name', label: 'Grado', hideOnMobile: true },
    { key: 'subject_name', label: 'Materia' },
    { key: 'teacher_name', label: 'Profesor', hideOnMobile: true },
    { key: 'progress', label: 'Progreso' },
    { key: 'average_score', label: 'Promedio' },
];

const fetchTerms = async () => {
    try {
        const response = await fetch('/api/v1/terms', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        terms.value = data.data || [];
        if (terms.value.length > 0 && !selectedTerm.value) {
            selectedTerm.value = String(terms.value[0].id);
        }
    } catch (error) {
        console.error('Error fetching terms:', error);
    }
};

const fetchScores = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page),
            per_page: '10',
        });
        if (selectedTerm.value) {
            params.append('term_id', selectedTerm.value);
        }
        
        const response = await fetch(`/api/v1/coordinator/scores-overview?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        sections.value = data.data || [];
        stats.value = data.stats || stats.value;
        pagination.value = data.pagination || null;
    } catch (error) {
        console.error('Error fetching scores:', error);
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (page: number) => {
    fetchScores(page);
};

const handleTermChange = () => {
    fetchScores(1);
};

const getScoreColor = (score: number) => {
    if (score >= 16) return 'text-green-600';
    if (score >= 10) return 'text-yellow-600';
    return 'text-red-600';
};

const getProgressColor = (entered: number, total: number) => {
    const percentage = total > 0 ? (entered / total) * 100 : 0;
    if (percentage >= 100) return 'green';
    if (percentage >= 50) return 'yellow';
    return 'red';
};

onMounted(async () => {
    await fetchTerms();
    fetchScores();
});
</script>

<template>
    <Head title="Seguimiento de Notas" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Seguimiento de Notas</h1>
                <select
                    v-model="selectedTerm"
                    @change="handleTermChange"
                    class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option v-for="term in terms" :key="term.id" :value="String(term.id)">
                        {{ term.name }}
                    </option>
                </select>
            </div>
        </template>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-blue-600">{{ stats.total_sections }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Total Secciones</div>
                </div>
            </Card>
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-green-600">{{ stats.sections_with_scores }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Con Notas</div>
                </div>
            </Card>
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold" :class="getScoreColor(stats.average_score)">
                        {{ stats.average_score.toFixed(1) }}
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500">Promedio General</div>
                </div>
            </Card>
            <Card>
                <div class="p-3 sm:p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-red-600">{{ stats.students_below_passing }}</div>
                    <div class="text-xs sm:text-sm text-gray-500">Estudiantes &lt; 10</div>
                </div>
            </Card>
        </div>

        <Card>
            <DataTable 
                :columns="columns" 
                :data="sections" 
                :loading="loading"
                :pagination="pagination"
                empty-message="No hay datos de notas disponibles"
                @page-change="handlePageChange"
            >
                <template #cell-section_name="{ item }">
                    <div>
                        <div class="font-medium text-gray-900">{{ item.section_name }}</div>
                        <div class="text-xs text-gray-500 md:hidden">{{ item.grade_name }}</div>
                    </div>
                </template>
                <template #cell-progress="{ item }">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-[100px]">
                            <div
                                class="h-2 rounded-full"
                                :class="{
                                    'bg-green-500': getProgressColor(item.scores_entered, item.students_count) === 'green',
                                    'bg-yellow-500': getProgressColor(item.scores_entered, item.students_count) === 'yellow',
                                    'bg-red-500': getProgressColor(item.scores_entered, item.students_count) === 'red',
                                }"
                                :style="{ width: `${item.students_count > 0 ? (item.scores_entered / item.students_count) * 100 : 0}%` }"
                            ></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ item.scores_entered }}/{{ item.students_count }}</span>
                    </div>
                </template>
                <template #cell-average_score="{ value }">
                    <span class="font-semibold" :class="getScoreColor(value)">
                        {{ value ? value.toFixed(1) : '-' }}
                    </span>
                </template>
            </DataTable>
        </Card>
    </AppLayout>
</template>
