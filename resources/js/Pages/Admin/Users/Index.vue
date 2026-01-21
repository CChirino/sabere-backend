<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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
import type { User, Role } from '@/types';

interface UserWithRoles extends User {
    roles: { name: Role }[];
    created_at: string;
}

const { get, loading, error } = useApi();
const users = ref<UserWithRoles[]>([]);

// SlideOver state
const showViewPanel = ref(false);
const showEditPanel = ref(false);
const showCreatePanel = ref(false);
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const selectedUser = ref<UserWithRoles | null>(null);
const deleting = ref(false);
const importErrors = ref<string[]>([]);

// Form for editing
const form = useForm({
    name: '',
    email: '',
    roles: [] as string[],
});

// Form for creating
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

// Form for importing
const importForm = useForm({
    file: null as File | null,
});

const availableRoles: { value: Role; label: string }[] = [
    { value: 'admin', label: 'Administrador' },
    { value: 'director', label: 'Director' },
    { value: 'coordinator', label: 'Coordinador' },
    { value: 'teacher', label: 'Profesor' },
    { value: 'student', label: 'Estudiante' },
    { value: 'guardian', label: 'Representante' },
];

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Roles' },
    { key: 'created_at', label: 'Registrado' },
];

const roleColors: Record<Role, 'blue' | 'purple' | 'green' | 'yellow' | 'red' | 'indigo'> = {
    admin: 'red',
    director: 'purple',
    coordinator: 'indigo',
    teacher: 'blue',
    student: 'green',
    guardian: 'yellow',
};

const roleLabels: Record<Role, string> = {
    admin: 'Administrador',
    director: 'Director',
    coordinator: 'Coordinador',
    teacher: 'Profesor',
    student: 'Estudiante',
    guardian: 'Representante',
};

const fetchUsers = async () => {
    const data = await get<UserWithRoles[]>('/api/v1/admin/users');
    if (data) {
        users.value = data;
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// View user details
const viewUser = (user: UserWithRoles) => {
    selectedUser.value = user;
    showViewPanel.value = true;
};

// Edit user
const editUser = (user: UserWithRoles) => {
    selectedUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.roles = user.roles.map(r => r.name);
    showEditPanel.value = true;
};

// Close panels
const closeViewPanel = () => {
    showViewPanel.value = false;
    selectedUser.value = null;
};

const closeEditPanel = () => {
    showEditPanel.value = false;
    selectedUser.value = null;
    form.reset();
};

// Submit edit form
const submitEdit = () => {
    if (!selectedUser.value) return;
    
    form.put(route('admin.users.update', selectedUser.value.id), {
        onSuccess: () => {
            closeEditPanel();
            fetchUsers();
        },
    });
};

// Toggle role selection
const toggleRole = (role: string) => {
    const index = form.roles.indexOf(role);
    if (index === -1) {
        form.roles.push(role);
    } else {
        form.roles.splice(index, 1);
    }
};

// Toggle role selection for create form
const toggleCreateRole = (role: string) => {
    const index = createForm.roles.indexOf(role);
    if (index === -1) {
        createForm.roles.push(role);
    } else {
        createForm.roles.splice(index, 1);
    }
};

// Open create panel
const openCreatePanel = () => {
    createForm.reset();
    showCreatePanel.value = true;
};

// Close create panel
const closeCreatePanel = () => {
    showCreatePanel.value = false;
    createForm.reset();
};

// Submit create form
const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            closeCreatePanel();
            fetchUsers();
        },
    });
};

// Delete user
const confirmDelete = (user: UserWithRoles) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedUser.value = null;
};

const deleteUser = () => {
    if (!selectedUser.value) return;
    
    deleting.value = true;
    router.delete(route('admin.users.destroy', selectedUser.value.id), {
        onSuccess: () => {
            closeDeleteModal();
            fetchUsers();
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

// Import functions
const openImportModal = () => {
    importForm.reset();
    importErrors.value = [];
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
    importErrors.value = [];
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.file = target.files[0];
    }
};

const submitImport = () => {
    if (!importForm.file) return;
    
    const formData = new FormData();
    formData.append('file', importForm.file);
    
    router.post(route('admin.users.import'), formData, {
        forceFormData: true,
        onSuccess: (page) => {
            const flash = page.props.flash as any;
            if (flash?.import_errors) {
                importErrors.value = flash.import_errors;
            } else {
                closeImportModal();
            }
            fetchUsers();
        },
        onError: (errors) => {
            if (errors.file) {
                importErrors.value = [errors.file];
            }
        },
    });
};

const downloadTemplate = () => {
    window.location.href = route('admin.users.template');
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
                <div class="flex items-center space-x-3">
                    <SecondaryButton @click="openImportModal">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importar
                    </SecondaryButton>
                    <PrimaryButton @click="openCreatePanel">Nuevo Usuario</PrimaryButton>
                </div>
            </div>
        </template>

        <!-- Error message -->
        <div v-if="error" class="mb-4 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ error }}</p>
                </div>
            </div>
        </div>

        <Card>
            <DataTable :columns="columns" :data="users" :loading="loading" empty-message="No hay usuarios registrados">
                <template #cell-roles="{ item }">
                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="role in item.roles"
                            :key="role.name"
                            :color="roleColors[role.name]"
                            size="sm"
                        >
                            {{ roleLabels[role.name] }}
                        </Badge>
                    </div>
                </template>
                <template #cell-created_at="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #actions="{ item }">
                    <div class="flex space-x-3">
                        <button
                            @click="viewUser(item)"
                            class="text-blue-600 hover:text-blue-900 font-medium text-sm"
                        >
                            Ver
                        </button>
                        <button
                            @click="editUser(item)"
                            class="text-indigo-600 hover:text-indigo-900 font-medium text-sm"
                        >
                            Editar
                        </button>
                        <button
                            @click="confirmDelete(item)"
                            class="text-red-600 hover:text-red-900 font-medium text-sm"
                        >
                            Eliminar
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- View User SlideOver -->
        <SlideOver
            :show="showViewPanel"
            title="Detalles del Usuario"
            max-width="md"
            @close="closeViewPanel"
        >
            <div v-if="selectedUser" class="space-y-6">
                <!-- Avatar & Name -->
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <span class="text-2xl font-bold text-white">
                            {{ selectedUser.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">{{ selectedUser.name }}</h3>
                        <p class="text-sm text-gray-500">ID: {{ selectedUser.id }}</p>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="space-y-4">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Correo Electrónico</h4>
                        <p class="text-gray-900">{{ selectedUser.email }}</p>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Roles</h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="role in selectedUser.roles"
                                :key="role.name"
                                :color="roleColors[role.name]"
                            >
                                {{ roleLabels[role.name] }}
                            </Badge>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Fecha de Registro</h4>
                        <p class="text-gray-900">{{ formatDateTime(selectedUser.created_at) }}</p>
                    </div>

                    <div v-if="selectedUser.email_verified_at" class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-green-800">Email verificado</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeViewPanel">
                        Cerrar
                    </SecondaryButton>
                    <PrimaryButton @click="closeViewPanel(); editUser(selectedUser!)">
                        Editar Usuario
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Edit User SlideOver -->
        <SlideOver
            :show="showEditPanel"
            title="Editar Usuario"
            max-width="md"
            @close="closeEditPanel"
        >
            <form @submit.prevent="submitEdit" class="space-y-6">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Nombre" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="email" value="Correo Electrónico" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <!-- Roles -->
                <div>
                    <InputLabel value="Roles" />
                    <div class="mt-2 space-y-2">
                        <label
                            v-for="role in availableRoles"
                            :key="role.value"
                            class="flex items-center p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="form.roles.includes(role.value) 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:bg-gray-50'"
                        >
                            <input
                                type="checkbox"
                                :value="role.value"
                                :checked="form.roles.includes(role.value)"
                                @change="toggleRole(role.value)"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="ml-3 flex items-center">
                                <Badge :color="roleColors[role.value]" size="sm" class="mr-2">
                                    {{ role.label }}
                                </Badge>
                            </span>
                        </label>
                    </div>
                    <InputError :message="form.errors.roles" class="mt-2" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeEditPanel" :disabled="form.processing">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="submitEdit" :disabled="form.processing">
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar Cambios</span>
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Create User SlideOver -->
        <SlideOver
            :show="showCreatePanel"
            title="Nuevo Usuario"
            max-width="md"
            @close="closeCreatePanel"
        >
            <form @submit.prevent="submitCreate" class="space-y-6">
                <!-- Name -->
                <div>
                    <InputLabel for="create-name" value="Nombre" />
                    <TextInput
                        id="create-name"
                        v-model="createForm.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        placeholder="Nombre completo"
                    />
                    <InputError :message="createForm.errors.name" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="create-email" value="Correo Electrónico" />
                    <TextInput
                        id="create-email"
                        v-model="createForm.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        placeholder="correo@ejemplo.com"
                    />
                    <InputError :message="createForm.errors.email" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <InputLabel for="create-password" value="Contraseña" />
                    <TextInput
                        id="create-password"
                        v-model="createForm.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        placeholder="Mínimo 8 caracteres"
                    />
                    <InputError :message="createForm.errors.password" class="mt-2" />
                </div>

                <!-- Password Confirmation -->
                <div>
                    <InputLabel for="create-password-confirmation" value="Confirmar Contraseña" />
                    <TextInput
                        id="create-password-confirmation"
                        v-model="createForm.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        placeholder="Repite la contraseña"
                    />
                </div>

                <!-- Roles -->
                <div>
                    <InputLabel value="Roles" />
                    <p class="text-sm text-gray-500 mb-2">Selecciona al menos un rol para el usuario</p>
                    <div class="mt-2 space-y-2">
                        <label
                            v-for="role in availableRoles"
                            :key="role.value"
                            class="flex items-center p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="createForm.roles.includes(role.value) 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:bg-gray-50'"
                        >
                            <input
                                type="checkbox"
                                :value="role.value"
                                :checked="createForm.roles.includes(role.value)"
                                @change="toggleCreateRole(role.value)"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="ml-3 flex items-center">
                                <Badge :color="roleColors[role.value]" size="sm" class="mr-2">
                                    {{ role.label }}
                                </Badge>
                            </span>
                        </label>
                    </div>
                    <InputError :message="createForm.errors.roles" class="mt-2" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-between">
                    <SecondaryButton @click="closeCreatePanel" :disabled="createForm.processing">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="submitCreate" :disabled="createForm.processing">
                        <span v-if="createForm.processing">Creando...</span>
                        <span v-else>Crear Usuario</span>
                    </PrimaryButton>
                </div>
            </template>
        </SlideOver>

        <!-- Import Modal -->
        <Modal :show="showImportModal" @close="closeImportModal" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>

                <div class="mt-4 text-center">
                    <h3 class="text-lg font-medium text-gray-900">
                        Importar Usuarios
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Sube un archivo Excel (.xlsx, .xls) o CSV con los datos de los usuarios.
                    </p>
                </div>

                <!-- Template download -->
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Plantilla de ejemplo</p>
                            <p class="text-xs text-gray-500">Descarga la plantilla con el formato correcto</p>
                        </div>
                        <button
                            @click="downloadTemplate"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Descargar
                        </button>
                    </div>
                </div>

                <!-- File format info -->
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm font-medium text-amber-800 mb-2">Formato requerido:</p>
                    <ul class="text-xs text-amber-700 space-y-1">
                        <li><strong>nombre</strong> - Nombre completo del usuario</li>
                        <li><strong>email</strong> - Correo electrónico (único)</li>
                        <li><strong>contrasena</strong> - Contraseña (mínimo 8 caracteres)</li>
                        <li><strong>roles</strong> - Roles separados por coma (admin, director, coordinator, teacher, student, guardian)</li>
                    </ul>
                </div>

                <!-- File input -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Archivo</label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mb-2 text-sm text-gray-500">
                                    <span v-if="importForm.file" class="font-semibold text-blue-600">{{ importForm.file.name }}</span>
                                    <span v-else><span class="font-semibold">Haz clic para subir</span> o arrastra el archivo</span>
                                </p>
                                <p class="text-xs text-gray-500">.xlsx, .xls o .csv (máx. 5MB)</p>
                            </div>
                            <input 
                                type="file" 
                                class="hidden" 
                                accept=".xlsx,.xls,.csv"
                                @change="handleFileChange"
                            />
                        </label>
                    </div>
                </div>

                <!-- Import errors -->
                <div v-if="importErrors.length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg max-h-40 overflow-y-auto">
                    <p class="text-sm font-medium text-red-800 mb-2">Errores encontrados:</p>
                    <ul class="text-xs text-red-700 space-y-1">
                        <li v-for="(err, index) in importErrors" :key="index">{{ err }}</li>
                    </ul>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeImportModal" :disabled="importForm.processing">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="submitImport" :disabled="importForm.processing || !importForm.file">
                        <span v-if="importForm.processing">Importando...</span>
                        <span v-else>Importar Usuarios</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <div class="mt-4 text-center">
                    <h3 class="text-lg font-medium text-gray-900">
                        Eliminar Usuario
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        ¿Estás seguro de que deseas eliminar a <strong>{{ selectedUser?.name }}</strong>? 
                        Esta acción no se puede deshacer.
                    </p>
                </div>

                <div class="mt-6 flex justify-center space-x-3">
                    <SecondaryButton @click="closeDeleteModal" :disabled="deleting">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton @click="deleteUser" :disabled="deleting">
                        <span v-if="deleting">Eliminando...</span>
                        <span v-else>Eliminar</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
