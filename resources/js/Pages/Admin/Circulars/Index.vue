<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps<{ circulars: any; periods: any[] }>();

const showCreate = ref(false);
const showEdit = ref(false);
const editing = ref<any>(null);

const createForm = useForm({
    title: '', content: '', priority: 'normal', audience: 'all',
    academic_period_id: '', send_email: false, send_push: false, scheduled_at: '',
});

const editForm = useForm({
    title: '', content: '', priority: 'normal', audience: 'all',
    academic_period_id: '', send_email: false, send_push: false, scheduled_at: '',
});

const priorityColor = (p: string) => {
    const map: Record<string, string> = { urgent: 'red', high: 'orange', normal: 'blue', low: 'gray' };
    return map[p] || 'gray';
};

const openEdit = (c: any) => {
    editing.value = c;
    editForm.title = c.title;
    editForm.content = c.content;
    editForm.priority = c.priority;
    editForm.audience = c.audience;
    editForm.academic_period_id = c.academic_period_id ? String(c.academic_period_id) : '';
    editForm.send_email = c.send_email;
    editForm.send_push = c.send_push;
    editForm.scheduled_at = c.scheduled_at ? c.scheduled_at.slice(0, 16) : '';
    showEdit.value = true;
};

const submitCreate = () => createForm.post(route('admin.circulars.store'), {
    onSuccess: () => { showCreate.value = false; createForm.reset(); },
});

const submitEdit = () => editForm.put(route('admin.circulars.update', editing.value.id), {
    onSuccess: () => { showEdit.value = false; editing.value = null; },
});

const destroy = (id: number) => {
    if (confirm('¿Eliminar esta circular?')) router.delete(route('admin.circulars.destroy', id));
};

const sendNow = (id: number) => {
    if (confirm('¿Enviar esta circular ahora?')) router.post(route('admin.circulars.send', id));
};
</script>

<template>
    <AppLayout title="Gestión de Circulares">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div><h1 class="text-2xl font-bold text-gray-900">Circulares</h1><p class="mt-1 text-sm text-gray-500">Crea y gestiona comunicados para la comunidad</p></div>
                <Button @click="showCreate = true">+ Nueva Circular</Button>
            </div>

            <div v-if="circulars.data.length === 0"><Card><EmptyState title="Sin circulares" description="Crea la primera circular usando el botón superior." /></Card></div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Título</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Prioridad</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Audiencia</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Fecha</th><th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="c in circulars.data" :key="c.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ c.title }}</div><div class="text-xs text-gray-400">{{ c.creator?.name }}</div></td>
                        <td class="px-4 py-3"><Badge :color="priorityColor(c.priority) as any">{{ c.priority }}</Badge></td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ c.audience }}</td>
                        <td class="px-4 py-3"><Badge v-if="c.sent_at" color="green">Enviada</Badge><Badge v-else-if="c.scheduled_at" color="yellow">Programada</Badge><Badge v-else color="gray">Borrador</Badge></td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ new Date(c.created_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3 text-right space-x-2 text-sm">
                            <button v-if="!c.sent_at" @click="openEdit(c)" class="text-blue-600 hover:text-blue-800">Editar</button>
                            <button v-if="!c.sent_at" @click="sendNow(c.id)" class="text-green-600 hover:text-green-800">Enviar</button>
                            <button @click="destroy(c.id)" class="text-red-600 hover:text-red-800">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
                </table>
            </div>
            <Pagination v-if="circulars.data.length > 0" :links="circulars.links" :current-page="circulars.current_page" :last-page="circulars.last_page" :total="circulars.total" />
        </div>

        <SlideOver :show="showCreate" title="Nueva Circular" @close="showCreate = false">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">Título *</label><input v-model="createForm.title" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><InputError :message="createForm.errors.title" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Contenido *</label><textarea v-model="createForm.content" rows="6" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><InputError :message="createForm.errors.content" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Prioridad</label><select v-model="createForm.priority" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Audiencia</label><select v-model="createForm.audience" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="all">Todos</option><option value="teachers">Profesores</option><option value="students">Estudiantes</option><option value="guardians">Representantes</option><option value="staff">Personal</option></select></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Período académico</label><select v-model="createForm.academic_period_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="">Sin período</option><option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                <div class="flex items-center gap-4"><label class="flex items-center gap-2"><input v-model="createForm.send_email" type="checkbox" class="rounded border-gray-300" /><span class="text-sm text-gray-700">Enviar por email</span></label><label class="flex items-center gap-2"><input v-model="createForm.send_push" type="checkbox" class="rounded border-gray-300" /><span class="text-sm text-gray-700">Enviar notificación push</span></label></div>
                <div><label class="block text-sm font-medium text-gray-700">Programar envío</label><input v-model="createForm.scheduled_at" type="datetime-local" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><p class="mt-1 text-xs text-gray-400">Dejar en blanco para enviar inmediatamente</p></div>
                <div class="flex justify-end gap-2 pt-4"><Button variant="secondary" @click="showCreate = false">Cancelar</Button><Button :disabled="createForm.processing" type="submit">{{ createForm.processing ? 'Creando...' : 'Crear' }}</Button></div>
            </form>
        </SlideOver>

        <SlideOver :show="showEdit" title="Editar Circular" @close="showEdit = false">
            <form v-if="editing" @submit.prevent="submitEdit" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">Título *</label><input v-model="editForm.title" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><InputError :message="editForm.errors.title" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Contenido *</label><textarea v-model="editForm.content" rows="6" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><InputError :message="editForm.errors.content" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Prioridad</label><select v-model="editForm.priority" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Audiencia</label><select v-model="editForm.audience" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="all">Todos</option><option value="teachers">Profesores</option><option value="students">Estudiantes</option><option value="guardians">Representantes</option><option value="staff">Personal</option></select></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Período académico</label><select v-model="editForm.academic_period_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="">Sin período</option><option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                <div class="flex items-center gap-4"><label class="flex items-center gap-2"><input v-model="editForm.send_email" type="checkbox" class="rounded border-gray-300" /><span class="text-sm text-gray-700">Enviar por email</span></label><label class="flex items-center gap-2"><input v-model="editForm.send_push" type="checkbox" class="rounded border-gray-300" /><span class="text-sm text-gray-700">Enviar notificación push</span></label></div>
                <div class="flex justify-end gap-2 pt-4"><Button variant="secondary" @click="showEdit = false">Cancelar</Button><Button :disabled="editForm.processing" type="submit">{{ editForm.processing ? 'Guardando...' : 'Guardar' }}</Button></div>
            </form>
        </SlideOver>
    </AppLayout>
</template>
