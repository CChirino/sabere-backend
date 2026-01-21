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
import type { AcademicPeriod, Term } from '@/types';

const { get, loading, error } = useApi();
const periods = ref<AcademicPeriod[]>([]);

// Panel states
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const selectedPeriod = ref<AcademicPeriod | null>(null);
const deleting = ref(false);

// Terms management
const showTermsPanel = ref(false);
const terms = ref<Term[]>([]);
const loadingTerms = ref(false);
const termForm = useForm({
    academic_period_id: 0,
    name: '',
    number: 1,
    start_date: '',
    end_date: '',
    weight: 33.33,
    status: true,
});
const editingTerm = ref<Term | null>(null);

// Form for editing
const form = useForm({
    name: '',
    code: '',
    school_year: '',
    start_date: '',
    end_date: '',
    status: true,
});

// Form for creating
const createForm = useForm({
    name: '',
    code: '',
    school_year: '',
    start_date: '',
    end_date: '',
    status: true,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Nombre' },
    { key: 'code', label: 'Código' },
    { key: 'school_year', label: 'Año Escolar' },
    { key: 'start_date', label: 'Inicio' },
    { key: 'end_date', label: 'Fin' },
    { key: 'status', label: 'Estado' },
];

const fetchPeriods = async () => {
    const data = await get<AcademicPeriod[]>('/api/v1/academic-periods');
    if (data) {
        periods.value = data;
    }
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
const viewPeriod = (period: AcademicPeriod) => {
    selectedPeriod.value = period;
    showViewPanel.value = true;
};

const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedPeriod.value = null;
};

// Edit
const editPeriod = (period: AcademicPeriod) => {
    selectedPeriod.value = period;
    form.name = period.name;
    form.code = period.code;
    form.school_year = period.school_year;
    form.start_date = formatDateForInput(period.start_date);
    form.end_date = formatDateForInput(period.end_date);
    form.status = period.status;
    showEditPanel.value = true;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedPeriod.value = null;
    form.reset();
};

const submitEdit = () => {
    if (!selectedPeriod.value) return;
    form.put(route('academic.periods.update', selectedPeriod.value.id), {
        onSuccess: () => {
            closeEditPanel();
            fetchPeriods();
        },
    });
};

// Create
const openCreatePanel = () => {
    createForm.reset();
    // Set default school year
    const currentYear = new Date().getFullYear();
    createForm.school_year = `${currentYear}-${currentYear + 1}`;
    showCreatePanel.value = true;
};

const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('academic.periods.store'), {
        onSuccess: () => {
            closeCreatePanel();
            fetchPeriods();
        },
    });
};

// Delete
const confirmDelete = (period: AcademicPeriod) => {
    selectedPeriod.value = period;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedPeriod.value = null;
};

const deletePeriod = () => {
    if (!selectedPeriod.value) return;
    deleting.value = true;
    router.delete(route('academic.periods.destroy', selectedPeriod.value.id), {
        onSuccess: () => {
            closeDeleteModal();
            fetchPeriods();
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

// Terms management functions
const openTermsPanel = async (period: AcademicPeriod) => {
    selectedPeriod.value = period;
    showTermsPanel.value = true;
    await fetchTerms(period.id);
};

const closeTermsPanel = () => {
    showTermsPanel.value = false;
    selectedPeriod.value = null;
    terms.value = [];
    termForm.reset();
    editingTerm.value = null;
};

const fetchTerms = async (periodId: number) => {
    loadingTerms.value = true;
    try {
        const response = await fetch(`/api/v1/terms?academic_period_id=${periodId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        terms.value = data.data || [];
    } catch (error) {
        console.error('Error fetching terms:', error);
    } finally {
        loadingTerms.value = false;
    }
};

const initTermForm = (number: number = 1) => {
    if (!selectedPeriod.value) return;
    termForm.academic_period_id = selectedPeriod.value.id;
    termForm.name = `Lapso ${number}`;
    termForm.number = number;
    termForm.start_date = '';
    termForm.end_date = '';
    termForm.weight = 33.33;
    termForm.status = true;
    editingTerm.value = null;
};

const editTerm = (term: Term) => {
    editingTerm.value = term;
    termForm.academic_period_id = term.academic_period_id;
    termForm.name = term.name;
    termForm.number = term.number;
    termForm.start_date = term.start_date.split('T')[0];
    termForm.end_date = term.end_date.split('T')[0];
    termForm.weight = term.weight || 33.33;
    termForm.status = term.status;
};

const saveTerm = async () => {
    if (!selectedPeriod.value) return;
    
    const url = editingTerm.value 
        ? `/api/v1/terms/${editingTerm.value.id}`
        : '/api/v1/terms';
    const method = editingTerm.value ? 'PUT' : 'POST';
    
    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'include',
            body: JSON.stringify({
                academic_period_id: termForm.academic_period_id,
                name: termForm.name,
                number: termForm.number,
                start_date: termForm.start_date,
                end_date: termForm.end_date,
                weight: termForm.weight,
                status: termForm.status,
            }),
        });
        
        if (response.ok) {
            await fetchTerms(selectedPeriod.value.id);
            termForm.reset();
            editingTerm.value = null;
        }
    } catch (error) {
        console.error('Error saving term:', error);
    }
};

const deleteTerm = async (term: Term) => {
    if (!confirm(`¿Estás seguro de eliminar ${term.name}?`)) return;
    if (!selectedPeriod.value) return;
    
    try {
        const response = await fetch(`/api/v1/terms/${term.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'include',
        });
        
        if (response.ok) {
            await fetchTerms(selectedPeriod.value.id);
        }
    } catch (error) {
        console.error('Error deleting term:', error);
    }
};

const cancelTermEdit = () => {
    termForm.reset();
    editingTerm.value = null;
};

onMounted(() => {
    fetchPeriods();
});
</script>

<template>
    <Head title="Períodos Académicos" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Períodos Académicos</h1>
                <PrimaryButton @click="openCreatePanel">Nuevo Período</PrimaryButton>
            </div>
        </template>

        <Card>
            <DataTable :columns="columns" :data="periods" :loading="loading" empty-message="No hay períodos registrados">
                <template #cell-start_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-end_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-status="{ value }">
                    <Badge :color="value ? 'green' : 'gray'">
                        {{ value ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button @click="openTermsPanel(item)" class="text-green-600 hover:text-green-900 font-medium text-sm">
                            Lapsos
                        </button>
                        <button @click="viewPeriod(item)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                            Ver
                        </button>
                        <button @click="editPeriod(item)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                            Editar
                        </button>
                        <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900 font-medium text-sm">
                            Eliminar
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View SlideOver -->
        <SlideOver :show="showViewPanel" title="Detalles del Período" @close="closeViewPanel">
            <div v-if="selectedPeriod" class="space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Nombre</h4>
                    <p class="text-gray-900 font-medium">{{ selectedPeriod.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Código</h4>
                        <p class="text-gray-900">{{ selectedPeriod.code }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Año Escolar</h4>
                        <p class="text-gray-900">{{ selectedPeriod.school_year }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha Inicio</h4>
                        <p class="text-gray-900">{{ formatDate(selectedPeriod.start_date) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha Fin</h4>
                        <p class="text-gray-900">{{ formatDate(selectedPeriod.end_date) }}</p>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Estado</h4>
                    <Badge :color="selectedPeriod.status ? 'green' : 'gray'">
                        {{ selectedPeriod.status ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">Cerrar</SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editPeriod(selectedPeriod!)">Editar</PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit SlideOver -->
        <SlideOver :show="showEditPanel" title="Editar Período" @close="closeEditPanel">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <InputLabel for="edit-name" value="Nombre" />
                    <TextInput id="edit-name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-code" value="Código" />
                        <TextInput id="edit-code" v-model="form.code" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-school_year" value="Año Escolar" />
                        <TextInput id="edit-school_year" v-model="form.school_year" type="text" class="mt-1 block w-full" placeholder="2024-2025" required />
                        <InputError :message="form.errors.school_year" class="mt-2" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-start_date" value="Fecha Inicio" />
                        <TextInput id="edit-start_date" v-model="form.start_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit-end_date" value="Fecha Fin" />
                        <TextInput id="edit-end_date" v-model="form.end_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.end_date" class="mt-2" />
                    </div>
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
        <SlideOver :show="showCreatePanel" title="Nuevo Período Académico" @close="closeCreatePanel">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <InputLabel for="create-name" value="Nombre" />
                    <TextInput id="create-name" v-model="createForm.name" type="text" class="mt-1 block w-full" placeholder="Ej: Primer Período 2024-2025" required />
                    <InputError :message="createForm.errors.name" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-code" value="Código" />
                        <TextInput id="create-code" v-model="createForm.code" type="text" class="mt-1 block w-full" placeholder="Ej: 2024-2025-1" required />
                        <InputError :message="createForm.errors.code" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-school_year" value="Año Escolar" />
                        <TextInput id="create-school_year" v-model="createForm.school_year" type="text" class="mt-1 block w-full" placeholder="2024-2025" required />
                        <InputError :message="createForm.errors.school_year" class="mt-2" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="create-start_date" value="Fecha Inicio" />
                        <TextInput id="create-start_date" v-model="createForm.start_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="createForm.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create-end_date" value="Fecha Fin" />
                        <TextInput id="create-end_date" v-model="createForm.end_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="createForm.errors.end_date" class="mt-2" />
                    </div>
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
                        {{ createForm.processing ? 'Creando...' : 'Crear Período' }}
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
                    <h3 class="text-lg font-medium text-gray-900">Eliminar Período</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de eliminar <strong>{{ selectedPeriod?.name }}</strong>? Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">Cancelar</SecondaryButton>
                    <DangerButton @click="deletePeriod" :disabled="deleting">
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Terms Management SlideOver -->
        <SlideOver :show="showTermsPanel" title="Gestión de Lapsos" @close="closeTermsPanel" size="lg">
            <div v-if="selectedPeriod" class="space-y-6">
                <!-- Period Info -->
                <div class="bg-sabere-primary/10 rounded-lg p-4">
                    <h4 class="font-medium text-sabere-primary">{{ selectedPeriod.name }}</h4>
                    <p class="text-sm text-gray-600">{{ formatDate(selectedPeriod.start_date) }} - {{ formatDate(selectedPeriod.end_date) }}</p>
                </div>

                <!-- Terms List -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Lapsos del Período</h4>
                    
                    <div v-if="loadingTerms" class="text-center py-4">
                        <svg class="h-6 w-6 animate-spin text-sabere-accent mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-else-if="terms.length === 0" class="text-center py-6 bg-amber-50 rounded-lg border border-amber-200">
                        <svg class="mx-auto h-10 w-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="mt-2 text-amber-700 font-medium">No hay lapsos configurados</p>
                        <p class="text-sm text-amber-600">Agrega los lapsos para este período académico.</p>
                    </div>

                    <div v-else class="space-y-2">
                        <div 
                            v-for="term in terms" 
                            :key="term.id"
                            class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:border-sabere-accent transition-colors"
                        >
                            <div>
                                <span class="font-medium text-gray-900">{{ term.name }}</span>
                                <p class="text-sm text-gray-500">
                                    {{ formatDate(term.start_date) }} - {{ formatDate(term.end_date) }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Badge :color="term.status ? 'green' : 'gray'" size="sm">
                                    {{ term.status ? 'Activo' : 'Inactivo' }}
                                </Badge>
                                <button @click="editTerm(term)" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                    Editar
                                </button>
                                <button @click="deleteTerm(term)" class="text-red-600 hover:text-red-900 text-sm">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Term Form -->
                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">
                        {{ editingTerm ? 'Editar Lapso' : 'Agregar Nuevo Lapso' }}
                    </h4>
                    
                    <div class="space-y-4 bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="term-name" value="Nombre" />
                                <TextInput id="term-name" v-model="termForm.name" type="text" class="mt-1 block w-full" placeholder="Ej: Lapso 1" />
                            </div>
                            <div>
                                <InputLabel for="term-number" value="Número" />
                                <TextInput id="term-number" v-model="termForm.number" type="number" min="1" max="4" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="term-start" value="Fecha Inicio" />
                                <TextInput id="term-start" v-model="termForm.start_date" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="term-end" value="Fecha Fin" />
                                <TextInput id="term-end" v-model="termForm.end_date" type="date" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="term-weight" value="Peso (%)" />
                                <TextInput id="term-weight" v-model="termForm.weight" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full" />
                            </div>
                            <div class="flex items-end pb-1">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="termForm.status" />
                                    <span class="ml-2 text-sm text-gray-700">Activo</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <SecondaryButton v-if="editingTerm" @click="cancelTermEdit" type="button">
                                Cancelar
                            </SecondaryButton>
                            <PrimaryButton @click="saveTerm" type="button">
                                {{ editingTerm ? 'Actualizar' : 'Agregar Lapso' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Quick Add Buttons -->
                <div v-if="terms.length < 3" class="border-t pt-4">
                    <p class="text-sm text-gray-500 mb-2">Agregar rápidamente:</p>
                    <div class="flex space-x-2">
                        <button 
                            v-for="n in [1, 2, 3].filter(num => !terms.find(t => t.number === num))"
                            :key="n"
                            @click="initTermForm(n)"
                            class="px-3 py-1 text-sm bg-sabere-primary/10 text-sabere-primary rounded hover:bg-sabere-primary hover:text-white transition-colors"
                        >
                            Lapso {{ n }}
                        </button>
                    </div>
                </div>
            </div>

            <template #footer>
                <SecondaryButton @click="closeTermsPanel">Cerrar</SecondaryButton>
            </template>
        </SlideOver>
    </AppLayout>
</template>
