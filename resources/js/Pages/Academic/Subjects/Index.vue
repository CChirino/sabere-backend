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
import type { Subject } from '@/types';

interface SubjectArea {
    id: number;
    name: string;
    code: string;
}

interface SubjectWithArea extends Subject {
    subject_area: SubjectArea;
}

const { get, loading } = useApi();
const subjects = ref<SubjectWithArea[]>([]);
const subjectAreas = ref<SubjectArea[]>([]);

// Panel states
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const selectedSubject = ref<SubjectWithArea | null>(null);
const deleting = ref(false);

// Forms
const form = useForm({
    subject_area_id: '',
    name: '',
    code: '',
    description: '',
    status: true,
});

const createForm = useForm({
    subject_area_id: '',
    name: '',
    code: '',
    description: '',
    status: true,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Materia' },
    { key: 'code', label: 'Código' },
    { key: 'subject_area.name', label: 'Área' },
    { key: 'status', label: 'Estado' },
];

const fetchSubjects = async () => {
    const data = await get<SubjectWithArea[]>('/api/v1/subjects');
    if (data) subjects.value = data;
};

const fetchSubjectAreas = async () => {
    const data = await get<SubjectArea[]>('/api/v1/subject-areas');
    if (data) subjectAreas.value = data;
};

// View
const viewSubject = (subject: SubjectWithArea) => {
    selectedSubject.value = subject;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedSubject.value = null;
};

// Edit
const editSubject = (subject: SubjectWithArea) => {
    selectedSubject.value = subject;
    form.subject_area_id = String(subject.subject_area_id);
    form.name = subject.name;
    form.code = subject.code;
    form.description = subject.description || '';
    form.status = subject.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedSubject.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedSubject.value) return;
    form.put(route('academic.subjects.update', selectedSubject.value.id), {
        onSuccess: () => { closeEditPanel(); fetchSubjects(); },
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
    createForm.post(route('academic.subjects.store'), {
        onSuccess: () => { closeCreatePanel(); fetchSubjects(); },
    });
};

// Delete
const confirmDelete = (subject: SubjectWithArea) => {
    selectedSubject.value = subject;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedSubject.value = null;
};

const deleteSubject = () => {
    if (!selectedSubject.value) return;
    deleting.value = true;
    router.delete(route('academic.subjects.destroy', selectedSubject.value.id), {
        onSuccess: () => { closeDeleteModal(); fetchSubjects(); },
        onFinish: () => { deleting.value = false; },
    });
};

onMounted(() => {
    fetchSubjects();
    fetchSubjectAreas();
});
</script>

<template>
    <Head title="Materias" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Materias</h1>
                <PrimaryButton @click="openCreatePanel">Nueva Materia</PrimaryButton>
            </div>
        </template>

        <Card>
            <DataTable :columns="columns" :data="subjects" :loading="loading" empty-message="No hay materias registradas">
                <template #cell-status="{ value }">
                    <Badge :color="value ? 'green' : 'gray'">
                        {{ value ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button @click="viewSubject(item)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Ver</button>
                        <button @click="editSubject(item)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Editar</button>
                        <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900 font-medium text-sm">Eliminar</button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles de la Materia" @close="closeViewPanel">
            <div v-if="selectedSubject" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Nombre</h4>
                    <p class="text-gray-900 font-medium">{{ selectedSubject.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Código</h4>
                        <p class="text-gray-900">{{ selectedSubject.code }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Área</h4>
                        <p class="text-gray-900">{{ selectedSubject.subject_area?.name }}</p>
                    </div>
                </div>
                <div v-if="selectedSubject.description" class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Descripción</h4>
                    <p class="text-gray-900">{{ selectedSubject.description }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                    <Badge :color="selectedSubject.status ? 'green' : 'gray'">
                        {{ selectedSubject.status ? 'Activa' : 'Inactiva' }}
                    </Badge>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editSubject(selectedSubject!)">Editar</PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Materia" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-area" value="Área de Conocimiento" />
                    <select id="edit-area" v-model="form.subject_area_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar área</option>
                        <option v-for="area in subjectAreas" :key="area.id" :value="area.id">{{ area.name }}</option>
                    </select>
                    <InputError :message="form.errors.subject_area_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-name" value="Nombre" />
                        <TextInput id="edit-name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-code" value="Código" />
                        <TextInput id="edit-code" v-model="form.code" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="edit-description" value="Descripción" />
                    <textarea id="edit-description" v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"></textarea>
                    <InputError :message="form.errors.description" class="mt-2" />
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
        <SlideOver :show="showCreatePanel" title="Nueva Materia" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-area" value="Área de Conocimiento" />
                    <select id="create-area" v-model="createForm.subject_area_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" required>
                        <option value="">Seleccionar área</option>
                        <option v-for="area in subjectAreas" :key="area.id" :value="area.id">{{ area.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.subject_area_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-name" value="Nombre" />
                        <TextInput id="create-name" v-model="createForm.name" type="text" class="mt-1 block w-full" placeholder="Ej: Matemáticas" required />
                        <InputError :message="createForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-code" value="Código" />
                        <TextInput id="create-code" v-model="createForm.code" type="text" class="mt-1 block w-full" placeholder="Ej: MAT" required />
                        <InputError :message="createForm.errors.code" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="create-description" value="Descripción" />
                    <textarea id="create-description" v-model="createForm.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent" placeholder="Descripción de la materia (opcional)"></textarea>
                    <InputError :message="createForm.errors.description" class="mt-2" />
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
                        {{ createForm.processing ? 'Creando...' : 'Crear Materia' }}
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Materia</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ selectedSubject?.name }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteSubject" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
