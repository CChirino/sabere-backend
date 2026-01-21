<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useApi } from '@/composables/useApi';
import type { Enrollment, Section, AcademicPeriod, User } from '@/types';

interface EnrollmentWithRelations extends Enrollment {
    student: User;
    section: Section & { grade: { name: string } };
    academic_period: AcademicPeriod;
}

const { get, loading } = useApi();
const enrollments = ref<EnrollmentWithRelations[]>([]);
const sections = ref<Section[]>([]);
const periods = ref<AcademicPeriod[]>([]);
const students = ref<User[]>([]);

// Panel states
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const selectedEnrollment = ref<EnrollmentWithRelations | null>(null);
const deleting = ref(false);

// Forms
const form = useForm({
    student_id: '',
    section_id: '',
    academic_period_id: '',
    enrollment_date: '',
    status: 'active',
    notes: '',
});

const createForm = useForm({
    student_id: '',
    section_id: '',
    academic_period_id: '',
    enrollment_date: new Date().toISOString().split('T')[0],
    status: 'active',
    notes: '',
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'student.name', label: 'Estudiante' },
    { key: 'section.grade.name', label: 'Grado' },
    { key: 'section.name', label: 'Sección' },
    { key: 'enrollment_date', label: 'Fecha' },
    { key: 'status', label: 'Estado' },
];

const statusColors: Record<string, 'green' | 'gray' | 'yellow' | 'blue' | 'red'> = {
    active: 'green',
    inactive: 'gray',
    transferred: 'yellow',
    graduated: 'blue',
    withdrawn: 'red',
};

const statusLabels: Record<string, string> = {
    active: 'Activo',
    inactive: 'Inactivo',
    transferred: 'Transferido',
    graduated: 'Graduado',
    withdrawn: 'Retirado',
};

const statusOptions = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
    { value: 'transferred', label: 'Transferido' },
    { value: 'graduated', label: 'Graduado' },
    { value: 'withdrawn', label: 'Retirado' },
];

const fetchEnrollments = async () => {
    const data = await get<EnrollmentWithRelations[]>('/api/v1/enrollments');
    if (data) enrollments.value = data;
};

const fetchSections = async () => {
    const data = await get<Section[]>('/api/v1/sections');
    if (data) sections.value = data;
};

const fetchPeriods = async () => {
    const data = await get<AcademicPeriod[]>('/api/v1/academic-periods');
    if (data) periods.value = data;
};

const fetchStudents = async () => {
    const data = await get<User[]>('/api/v1/admin/users?role=student');
    if (data) students.value = data;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateForInput = (date: string) => {
    return date ? date.split('T')[0] : '';
};

// View
const viewEnrollment = (enrollment: EnrollmentWithRelations) => {
    selectedEnrollment.value = enrollment;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedEnrollment.value = null;
};

// Edit
const editEnrollment = (enrollment: EnrollmentWithRelations) => {
    selectedEnrollment.value = enrollment;
    form.student_id = String(enrollment.student_id);
    form.section_id = String(enrollment.section_id);
    form.academic_period_id = String(enrollment.academic_period_id);
    form.enrollment_date = formatDateForInput(enrollment.enrollment_date);
    form.status = enrollment.status;
    form.notes = enrollment.notes || '';
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedEnrollment.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedEnrollment.value) return;
    form.put(route('academic.enrollments.update', selectedEnrollment.value.id), {
        onSuccess: () => { closeEditPanel(); fetchEnrollments(); },
    });
};

// Create
const openCreatePanel = () => {
    createForm.reset();
    createForm.enrollment_date = new Date().toISOString().split('T')[0];
    createForm.status = 'active';
    showCreatePanel.value = true;
};

const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('academic.enrollments.store'), {
        onSuccess: () => { closeCreatePanel(); fetchEnrollments(); },
    });
};

// Delete
const confirmDelete = (enrollment: EnrollmentWithRelations) => {
    selectedEnrollment.value = enrollment;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedEnrollment.value = null;
};

const deleteEnrollment = () => {
    if (!selectedEnrollment.value) return;
    deleting.value = true;
    router.delete(route('academic.enrollments.destroy', selectedEnrollment.value.id), {
        onSuccess: () => { closeDeleteModal(); fetchEnrollments(); },
        onFinish: () => { deleting.value = false; },
    });
};

onMounted(() => {
    fetchEnrollments();
    fetchSections();
    fetchPeriods();
    fetchStudents();
});
</script>

<template>
    <Head title="Inscripciones" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Inscripciones</h1>
                <PrimaryButton @click="openCreatePanel">Nueva Inscripción</PrimaryButton>
            </div>
        </template>

        <Card>
            <DataTable :columns="columns" :data="enrollments" :loading="loading" empty-message="No hay inscripciones registradas">
                <template #cell-enrollment_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-status="{ value }">
                    <Badge :color="statusColors[value] || 'gray'">
                        {{ statusLabels[value] || value }}
                    </Badge>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button @click="viewEnrollment(item)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Ver</button>
                        <button @click="editEnrollment(item)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Editar</button>
                        <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900 font-medium text-sm">Eliminar</button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles de Inscripción" @close="closeViewPanel">
            <div v-if="selectedEnrollment" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estudiante</h4>
                    <p class="text-gray-900 font-medium">{{ selectedEnrollment.student?.name }}</p>
                    <p class="text-sm text-gray-500">{{ selectedEnrollment.student?.email }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Grado</h4>
                        <p class="text-gray-900">{{ selectedEnrollment.section?.grade?.name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Sección</h4>
                        <p class="text-gray-900">{{ selectedEnrollment.section?.name }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Período</h4>
                        <p class="text-gray-900">{{ selectedEnrollment.academic_period?.name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha de Inscripción</h4>
                        <p class="text-gray-900">{{ formatDate(selectedEnrollment.enrollment_date) }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                    <Badge :color="statusColors[selectedEnrollment.status] || 'gray'">
                        {{ statusLabels[selectedEnrollment.status] || selectedEnrollment.status }}
                    </Badge>
                </div>
                <div v-if="selectedEnrollment.notes" class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Notas</h4>
                    <p class="text-gray-900">{{ selectedEnrollment.notes }}</p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editEnrollment(selectedEnrollment!)">Editar</PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Inscripción" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-student" value="Estudiante" />
                    <select id="edit-student" v-model="form.student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar estudiante</option>
                        <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }} ({{ student.email }})</option>
                    </select>
                    <InputError :message="form.errors.student_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-period" value="Período Académico" />
                    <select id="edit-period" v-model="form.academic_period_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar período</option>
                        <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option>
                    </select>
                    <InputError :message="form.errors.academic_period_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-section" value="Sección" />
                    <select id="edit-section" v-model="form.section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar sección</option>
                        <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.grade?.name }} - {{ section.name }}</option>
                    </select>
                    <InputError :message="form.errors.section_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-date" value="Fecha de Inscripción" />
                        <TextInput id="edit-date" v-model="form.enrollment_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.enrollment_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-status" value="Estado" />
                        <select id="edit-status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="edit-notes" value="Notas" />
                    <textarea id="edit-notes" v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" placeholder="Observaciones (opcional)"></textarea>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeEditPanel" :disabled="form.processing">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitEdit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Create SlideOver -->
        <SlideOver :show="showCreatePanel" title="Nueva Inscripción" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-student" value="Estudiante" />
                    <select id="create-student" v-model="createForm.student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar estudiante</option>
                        <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }} ({{ student.email }})</option>
                    </select>
                    <InputError :message="createForm.errors.student_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-period" value="Período Académico" />
                    <select id="create-period" v-model="createForm.academic_period_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar período</option>
                        <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.academic_period_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-section" value="Sección" />
                    <select id="create-section" v-model="createForm.section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar sección</option>
                        <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.grade?.name }} - {{ section.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.section_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-date" value="Fecha de Inscripción" />
                        <TextInput id="create-date" v-model="createForm.enrollment_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="createForm.errors.enrollment_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-status" value="Estado" />
                        <select id="create-status" v-model="createForm.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <InputError :message="createForm.errors.status" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="create-notes" value="Notas" />
                    <textarea id="create-notes" v-model="createForm.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" placeholder="Observaciones (opcional)"></textarea>
                    <InputError :message="createForm.errors.notes" class="mt-2" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeCreatePanel" :disabled="createForm.processing">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitCreate" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creando...' : 'Crear Inscripción' }}
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Inscripción</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar la inscripción de <strong>{{ selectedEnrollment?.student?.name }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteEnrollment" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
