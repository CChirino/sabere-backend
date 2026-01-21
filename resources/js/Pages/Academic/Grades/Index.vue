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
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useApi } from '@/composables/useApi';
import type { Grade, EducationLevel } from '@/types';

interface GradeWithLevel extends Grade {
    education_level: EducationLevel;
}

const { get, loading } = useApi();
const grades = ref<GradeWithLevel[]>([]);
const educationLevels = ref<EducationLevel[]>([]);

// Panel states
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const selectedGrade = ref<GradeWithLevel | null>(null);
const deleting = ref(false);

// Forms
const form = useForm({
    education_level_id: '',
    name: '',
    numeric_equivalent: 1,
    status: true,
});

const createForm = useForm({
    education_level_id: '',
    name: '',
    numeric_equivalent: 1,
    status: true,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Grado' },
    { key: 'numeric_equivalent', label: 'Equivalente' },
    { key: 'education_level.name', label: 'Nivel Educativo' },
    { key: 'status', label: 'Estado' },
];

const fetchGrades = async () => {
    const data = await get<GradeWithLevel[]>('/api/v1/grades');
    if (data) grades.value = data;
};

const fetchEducationLevels = async () => {
    const data = await get<EducationLevel[]>('/api/v1/education-levels');
    if (data) educationLevels.value = data;
};

// View
const viewGrade = (grade: GradeWithLevel) => {
    selectedGrade.value = grade;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedGrade.value = null;
};

// Edit
const editGrade = (grade: GradeWithLevel) => {
    selectedGrade.value = grade;
    form.education_level_id = String(grade.education_level_id);
    form.name = grade.name;
    form.numeric_equivalent = grade.numeric_equivalent;
    form.status = grade.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedGrade.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedGrade.value) return;
    form.put(route('academic.grades.update', selectedGrade.value.id), {
        onSuccess: () => { closeEditPanel(); fetchGrades(); },
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
    createForm.post(route('academic.grades.store'), {
        onSuccess: () => { closeCreatePanel(); fetchGrades(); },
    });
};

// Delete
const confirmDelete = (grade: GradeWithLevel) => {
    selectedGrade.value = grade;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedGrade.value = null;
};

const deleteGrade = () => {
    if (!selectedGrade.value) return;
    deleting.value = true;
    router.delete(route('academic.grades.destroy', selectedGrade.value.id), {
        onSuccess: () => { closeDeleteModal(); fetchGrades(); },
        onFinish: () => { deleting.value = false; },
    });
};

onMounted(() => {
    fetchGrades();
    fetchEducationLevels();
});
</script>

<template>
    <Head title="Grados" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Grados y Niveles</h1>
                <PrimaryButton @click="openCreatePanel">Nuevo Grado</PrimaryButton>
            </div>
        </template>

        <Card>
            <DataTable :columns="columns" :data="grades" :loading="loading" empty-message="No hay grados registrados">
                <template #cell-status="{ value }">
                    <Badge :color="value ? 'green' : 'gray'">
                        {{ value ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button @click="viewGrade(item)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Ver</button>
                        <button @click="editGrade(item)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Editar</button>
                        <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900 font-medium text-sm">Eliminar</button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles del Grado" @close="closeViewPanel">
            <div v-if="selectedGrade" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Nombre</h4>
                    <p class="text-gray-900 font-medium">{{ selectedGrade.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Nivel Educativo</h4>
                        <p class="text-gray-900">{{ selectedGrade.education_level?.name }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Equivalente Numérico</h4>
                        <p class="text-gray-900">{{ selectedGrade.numeric_equivalent }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                    <Badge :color="selectedGrade.status ? 'green' : 'gray'">
                        {{ selectedGrade.status ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editGrade(selectedGrade!)">Editar</PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Grado" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-level" value="Nivel Educativo" />
                    <select id="edit-level" v-model="form.education_level_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar nivel</option>
                        <option v-for="level in educationLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
                    </select>
                    <InputError :message="form.errors.education_level_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-name" value="Nombre del Grado" />
                    <TextInput id="edit-name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="edit-numeric" value="Equivalente Numérico" />
                    <TextInput id="edit-numeric" v-model="form.numeric_equivalent" type="number" min="1" max="12" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.numeric_equivalent" class="mt-2" />
                </div>
                <div class="flex items-center">
                    <Checkbox id="edit-status" v-model:checked="form.status" />
                    <InputLabel for="edit-status" value="Activo" class="ml-2" />
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
        <SlideOver :show="showCreatePanel" title="Nuevo Grado" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-level" value="Nivel Educativo" />
                    <select id="create-level" v-model="createForm.education_level_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar nivel</option>
                        <option v-for="level in educationLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.education_level_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-name" value="Nombre del Grado" />
                    <TextInput id="create-name" v-model="createForm.name" type="text" class="mt-1 block w-full" placeholder="Ej: Primer Grado" required />
                    <InputError :message="createForm.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="create-numeric" value="Equivalente Numérico" />
                    <TextInput id="create-numeric" v-model="createForm.numeric_equivalent" type="number" min="1" max="12" class="mt-1 block w-full" required />
                    <InputError :message="createForm.errors.numeric_equivalent" class="mt-2" />
                </div>
                <div class="flex items-center">
                    <Checkbox id="create-status" v-model:checked="createForm.status" />
                    <InputLabel for="create-status" value="Activo" class="ml-2" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeCreatePanel" :disabled="createForm.processing">Cancelar</SecondaryButton>
                    <PrimaryButton @click="submitCreate" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creando...' : 'Crear Grado' }}
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Grado</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ selectedGrade?.name }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteGrade" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
