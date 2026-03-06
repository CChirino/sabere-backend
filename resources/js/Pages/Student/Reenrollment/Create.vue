<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Alert from '@/Components/UI/Alert.vue';
import Badge from '@/Components/UI/Badge.vue';

interface Grade {
    id: number;
    name: string;
    sections: Section[];
}

interface Section {
    id: number;
    name: string;
    capacity: number;
}

interface AcademicPeriod {
    id: number;
    name: string;
    school_year: string;
    start_date: string;
    end_date: string;
}

interface Enrollment {
    id: number;
    section: {
        id: number;
        name: string;
        grade: {
            id: number;
            name: string;
        };
    };
    academic_period: AcademicPeriod;
}

const props = defineProps<{
    currentEnrollment: Enrollment;
    availablePeriods: AcademicPeriod[];
    nextGrades: Grade[];
}>();

const form = useForm({
    target_academic_period_id: '',
    target_grade_id: '',
    target_section_id: '',
    student_notes: '',
});

const selectedGrade = computed(() => {
    return props.nextGrades.find(g => g.id === Number(form.target_grade_id));
});

const availableSections = computed(() => {
    return selectedGrade.value?.sections || [];
});

watch(() => form.target_grade_id, () => {
    form.target_section_id = '';
});

const submit = () => {
    form.post(route('student.reenrollment.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const getPeriodLabel = (period: AcademicPeriod) => {
    return `${period.name} (${period.school_year})`;
};
</script>

<template>
    <AppLayout>
        <Head title="Solicitar Reinscripción" />

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Solicitud de Reinscripción</h1>
                    <p class="text-gray-600 mt-1">Completa el formulario para solicitar tu reinscripción en el siguiente período académico.</p>
                </div>

                <!-- Current Enrollment Info -->
                <Card class="mb-6">
                    <template #header>
                        <h2 class="text-lg font-semibold text-gray-900">Inscripción Actual</h2>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Período Académico</label>
                            <p class="text-gray-900 font-semibold">{{ currentEnrollment.academic_period.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Grado</label>
                            <p class="text-gray-900 font-semibold">{{ currentEnrollment.section.grade.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Sección</label>
                            <p class="text-gray-900 font-semibold">{{ currentEnrollment.section.name }}</p>
                        </div>
                    </div>
                </Card>

                <!-- Reenrollment Form -->
                <Card>
                    <template #header>
                        <h2 class="text-lg font-semibold text-gray-900">Nueva Solicitud</h2>
                    </template>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Academic Period -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Período Académico Destino <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="form.target_academic_period_id"
                                :options="availablePeriods.map(p => ({ value: p.id, label: getPeriodLabel(p) }))"
                                placeholder="Selecciona un período académico"
                                :error="form.errors.target_academic_period_id"
                                required
                            />
                        </div>

                        <!-- Grade -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Grado Destino <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="form.target_grade_id"
                                :options="nextGrades.map(g => ({ value: g.id, label: g.name }))"
                                placeholder="Selecciona el grado"
                                :error="form.errors.target_grade_id"
                                required
                            />
                        </div>

                        <!-- Section -->
                        <div v-if="availableSections.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Sección (Opcional)
                            </label>
                            <Select
                                v-model="form.target_section_id"
                                :options="availableSections.map(s => ({ value: s.id, label: s.name }))"
                                placeholder="Selecciona una sección (opcional)"
                                :error="form.errors.target_section_id"
                            />
                            <p class="text-sm text-gray-500 mt-1">
                                Si no seleccionas sección, serás asignado automáticamente.
                            </p>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Notas Adicionales
                            </label>
                            <textarea
                                v-model="form.student_notes"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="¿Hay algo que quieras comunicar a la administración sobre tu solicitud?"
                            ></textarea>
                            <p v-if="form.errors.student_notes" class="text-red-500 text-sm mt-1">{{ form.errors.student_notes }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Link
                                :href="route('student.reenrollment.status')"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                Ver mis solicitudes
                            </Link>
                            <Button
                                type="submit"
                                variant="primary"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
