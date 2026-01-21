<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useApi } from '@/composables/useApi';
import type { Section, Grade, AcademicPeriod } from '@/types';

interface SectionWithRelations extends Section {
    grade: Grade & { education_level: { name: string } };
    academic_period: AcademicPeriod;
}

const { get, loading } = useApi();
const sections = ref<SectionWithRelations[]>([]);
const grades = ref<Grade[]>([]);
const periods = ref<AcademicPeriod[]>([]);

// Panel states
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const selectedSection = ref<SectionWithRelations | null>(null);
const deleting = ref(false);

// Forms
const form = useForm({
    grade_id: '',
    academic_period_id: '',
    name: '',
    capacity: null as number | null,
    status: true,
});

const createForm = useForm({
    grade_id: '',
    academic_period_id: '',
    name: '',
    capacity: null as number | null,
    status: true,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'grade.name', label: 'Grado' },
    { key: 'name', label: 'Sección' },
    { key: 'grade.education_level.name', label: 'Nivel' },
    { key: 'capacity', label: 'Capacidad' },
    { key: 'enrollments_count', label: 'Inscritos' },
    { key: 'status', label: 'Estado' },
];

const fetchSections = async () => {
    const data = await get<SectionWithRelations[]>('/api/v1/sections');
    if (data) sections.value = data;
};

const fetchGrades = async () => {
    const data = await get<Grade[]>('/api/v1/grades');
    if (data) grades.value = data;
};

const fetchPeriods = async () => {
    const data = await get<AcademicPeriod[]>('/api/v1/academic-periods');
    if (data) periods.value = data;
};

// View
const viewSection = (section: SectionWithRelations) => {
    selectedSection.value = section;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedSection.value = null;
};

// Edit
const editSection = (section: SectionWithRelations) => {
    selectedSection.value = section;
    form.grade_id = String(section.grade_id);
    form.academic_period_id = String(section.academic_period_id);
    form.name = section.name;
    form.capacity = section.capacity || null;
    form.status = section.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedSection.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedSection.value) return;
    form.put(route('academic.sections.update', selectedSection.value.id), {
        onSuccess: () => { closeEditPanel(); fetchSections(); },
    });
};

// Create
const openCreatePanel = () => {
    createForm.reset();
    showCreatePanel.value = true;
};

const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('academic.sections.store'), {
        onSuccess: () => { closeCreatePanel(); fetchSections(); },
    });
};

// Delete
const confirmDelete = (section: SectionWithRelations) => {
    selectedSection.value = section;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedSection.value = null;
};

const deleteSection = () => {
    if (!selectedSection.value) return;
    deleting.value = true;
    router.delete(route('academic.sections.destroy', selectedSection.value.id), {
        onSuccess: () => { closeDeleteModal(); fetchSections(); },
        onFinish: () => { deleting.value = false; },
    });
};

onMounted(() => {
    fetchSections();
    fetchGrades();
    fetchPeriods();
});
</script>

<template>
    <Head title="Secciones" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Secciones</h1>
                <PrimaryButton @click="openCreatePanel">Nueva Sección</PrimaryButton>
            </div>
        </template>

        <Card>
            <DataTable :columns="columns" :data="sections" :loading="loading" empty-message="No hay secciones registradas">
                <template #cell-status="{ value }">
                    <Badge :color="value ? 'green' : 'red'">
                        {{ value ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </template>
                <template #cell-enrollments_count="{ value, item }">
                    <span :class="item.capacity && value >= item.capacity ? 'text-red-600 font-medium' : ''">
                        {{ value || 0 }}
                    </span>
                    <span v-if="item.capacity" class="text-gray-400"> / {{ item.capacity }}</span>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button @click="viewSection(item)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Ver</button>
                        <Link :href="route('academic.schedules.section', item.id)" class="text-green-600 hover:text-green-900 font-medium text-sm">Horario</Link>
                        <button @click="editSection(item)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Editar</button>
                        <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900 font-medium text-sm">Eliminar</button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles de la Sección" @close="closeViewPanel">
            <div v-if="selectedSection" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Sección</h4>
                    <p class="text-gray-900 font-medium">{{ selectedSection.grade?.name }} - {{ selectedSection.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Nivel Educativo</h4>
                        <p class="text-gray-900">{{ selectedSection.grade?.education_level?.name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Período</h4>
                        <p class="text-gray-900">{{ selectedSection.academic_period?.name }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Capacidad</h4>
                        <p class="text-gray-900">{{ selectedSection.capacity || 'Sin límite' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Inscritos</h4>
                        <p class="text-gray-900">{{ selectedSection.enrollments_count || 0 }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                    <Badge :color="selectedSection.status ? 'green' : 'gray'">
                        {{ selectedSection.status ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editSection(selectedSection!)">Editar</PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Sección" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-period" value="Período Académico" />
                    <select id="edit-period" v-model="form.academic_period_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar período</option>
                        <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option>
                    </select>
                    <InputError :message="form.errors.academic_period_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-grade" value="Grado" />
                    <select id="edit-grade" v-model="form.grade_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar grado</option>
                        <option v-for="grade in grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                    </select>
                    <InputError :message="form.errors.grade_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-name" value="Nombre de Sección" />
                        <TextInput id="edit-name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Ej: A, B, C" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-capacity" value="Capacidad" />
                        <TextInput id="edit-capacity" v-model="form.capacity" type="number" min="1" max="100" class="mt-1 block w-full" placeholder="Opcional" />
                        <InputError :message="form.errors.capacity" class="mt-2" />
                    </div>
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
        <SlideOver :show="showCreatePanel" title="Nueva Sección" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-period" value="Período Académico" />
                    <select id="create-period" v-model="createForm.academic_period_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar período</option>
                        <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.academic_period_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-grade" value="Grado" />
                    <select id="create-grade" v-model="createForm.grade_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar grado</option>
                        <option v-for="grade in grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.grade_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-name" value="Nombre de Sección" />
                        <TextInput id="create-name" v-model="createForm.name" type="text" class="mt-1 block w-full" placeholder="Ej: A, B, C" required />
                        <InputError :message="createForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-capacity" value="Capacidad" />
                        <TextInput id="create-capacity" v-model="createForm.capacity" type="number" min="1" max="100" class="mt-1 block w-full" placeholder="Opcional" />
                        <InputError :message="createForm.errors.capacity" class="mt-2" />
                    </div>
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
                        {{ createForm.processing ? 'Creando...' : 'Crear Sección' }}
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Sección</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ selectedSection?.grade?.name }} - {{ selectedSection?.name }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteSection" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
