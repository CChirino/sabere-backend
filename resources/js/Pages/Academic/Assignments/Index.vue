<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useApi } from '@/composables/useApi';
import type { SubjectAssignment, Section, Subject, AcademicPeriod, User } from '@/types';

interface AssignmentWithRelations extends SubjectAssignment {
    teacher: User;
    subject: Subject;
    section: Section & { grade: { name: string } };
    academic_period: AcademicPeriod;
}

const { get, loading } = useApi();
const assignments = ref<AssignmentWithRelations[]>([]);
const sections = ref<Section[]>([]);
const subjects = ref<Subject[]>([]);
const periods = ref<AcademicPeriod[]>([]);
const teachers = ref<User[]>([]);

// Panel states
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const selectedAssignment = ref<AssignmentWithRelations | null>(null);
const deleting = ref(false);

// Forms
const form = useForm({
    teacher_id: '',
    subject_id: '',
    section_id: '',
    academic_period_id: '',
    status: true,
});

const createForm = useForm({
    teacher_id: '',
    subject_id: '',
    section_id: '',
    academic_period_id: '',
    status: true,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'teacher.name', label: 'Profesor' },
    { key: 'subject.name', label: 'Materia' },
    { key: 'section.grade.name', label: 'Grado' },
    { key: 'section.name', label: 'Sección' },
    { key: 'status', label: 'Estado' },
];

const fetchAssignments = async () => {
    const data = await get<AssignmentWithRelations[]>('/api/v1/subject-assignments');
    if (data) assignments.value = data;
};

const fetchSections = async () => {
    const data = await get<Section[]>('/api/v1/sections');
    if (data) sections.value = data;
};

const fetchSubjects = async () => {
    const data = await get<Subject[]>('/api/v1/subjects');
    if (data) subjects.value = data;
};

const fetchPeriods = async () => {
    const data = await get<AcademicPeriod[]>('/api/v1/academic-periods');
    if (data) periods.value = data;
};

const fetchTeachers = async () => {
    const data = await get<User[]>('/api/v1/admin/users?role=teacher');
    if (data) teachers.value = data;
};

// View
const viewAssignment = (assignment: AssignmentWithRelations) => {
    selectedAssignment.value = assignment;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedAssignment.value = null;
};

// Edit
const editAssignment = (assignment: AssignmentWithRelations) => {
    selectedAssignment.value = assignment;
    form.teacher_id = String(assignment.teacher_id);
    form.subject_id = String(assignment.subject_id);
    form.section_id = String(assignment.section_id);
    form.academic_period_id = String(assignment.academic_period_id);
    form.status = assignment.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedAssignment.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedAssignment.value) return;
    form.put(route('academic.assignments.update', selectedAssignment.value.id), {
        onSuccess: () => { closeEditPanel(); fetchAssignments(); },
    });
};

// Create
const openCreatePanel = () => {
    createForm.reset();
    createForm.status = true;
    showCreatePanel.value = true;
};

const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('academic.assignments.store'), {
        onSuccess: () => { closeCreatePanel(); fetchAssignments(); },
    });
};

// Delete
const confirmDelete = (assignment: AssignmentWithRelations) => {
    selectedAssignment.value = assignment;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedAssignment.value = null;
};

const deleteAssignment = () => {
    if (!selectedAssignment.value) return;
    deleting.value = true;
    router.delete(route('academic.assignments.destroy', selectedAssignment.value.id), {
        onSuccess: () => { closeDeleteModal(); fetchAssignments(); },
        onFinish: () => { deleting.value = false; },
    });
};

onMounted(() => {
    fetchAssignments();
    fetchSections();
    fetchSubjects();
    fetchPeriods();
    fetchTeachers();
});
</script>

<template>
    <Head title="Asignaciones" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Asignaciones de Materias</h1>
                <PrimaryButton @click="openCreatePanel">Nueva Asignación</PrimaryButton>
            </div>
        </template>

        <Card>
            <DataTable :columns="columns" :data="assignments" :loading="loading" empty-message="No hay asignaciones registradas">
                <template #cell-status="{ value }">
                    <Badge :color="value ? 'green' : 'gray'">
                        {{ value ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button @click="viewAssignment(item)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Ver</button>
                        <button @click="editAssignment(item)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Editar</button>
                        <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900 font-medium text-sm">Eliminar</button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles de Asignación" @close="closeViewPanel">
            <div v-if="selectedAssignment" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Profesor</h4>
                    <p class="text-gray-900 font-medium">{{ selectedAssignment.teacher?.name }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Materia</h4>
                    <p class="text-gray-900">{{ selectedAssignment.subject?.name }}</p>
                    <p class="text-sm text-gray-500">{{ selectedAssignment.subject?.code }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Grado</h4>
                        <p class="text-gray-900">{{ selectedAssignment.section?.grade?.name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Sección</h4>
                        <p class="text-gray-900">{{ selectedAssignment.section?.name }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Período Académico</h4>
                    <p class="text-gray-900">{{ selectedAssignment.academic_period?.name }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                    <Badge :color="selectedAssignment.status ? 'green' : 'gray'">
                        {{ selectedAssignment.status ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editAssignment(selectedAssignment!)">Editar</PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Asignación" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-teacher" value="Profesor" />
                    <select id="edit-teacher" v-model="form.teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar profesor</option>
                        <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                    </select>
                    <InputError :message="form.errors.teacher_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-subject" value="Materia" />
                    <select id="edit-subject" v-model="form.subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar materia</option>
                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code }})</option>
                    </select>
                    <InputError :message="form.errors.subject_id" class="mt-2" />
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
                <div class="flex items-center">
                    <Checkbox id="edit-status" v-model:checked="form.status" />
                    <InputLabel for="edit-status" value="Activa" class="ml-2" />
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
        <SlideOver :show="showCreatePanel" title="Nueva Asignación" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-teacher" value="Profesor" />
                    <select id="create-teacher" v-model="createForm.teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar profesor</option>
                        <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.teacher_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-subject" value="Materia" />
                    <select id="create-subject" v-model="createForm.subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar materia</option>
                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code }})</option>
                    </select>
                    <InputError :message="createForm.errors.subject_id" class="mt-2" />
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
                <div class="flex items-center">
                    <Checkbox id="create-status" v-model:checked="createForm.status" />
                    <InputLabel for="create-status" value="Activa" class="ml-2" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeCreatePanel" :disabled="createForm.processing">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitCreate" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creando...' : 'Crear Asignación' }}
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Asignación</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar la asignación de <strong>{{ selectedAssignment?.subject?.name }}</strong> a <strong>{{ selectedAssignment?.teacher?.name }}</strong>?
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteAssignment" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
