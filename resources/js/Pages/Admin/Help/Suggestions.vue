<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps<{ suggestions: any }>();

const statusColor = (s: string) => {
    const map: Record<string, string> = { pending: 'yellow', reviewed: 'blue', implemented: 'green', rejected: 'red' };
    return map[s] || 'gray';
};

const statusLabel = (s: string) => {
    const map: Record<string, string> = { pending: 'Pendiente', reviewed: 'Revisado', implemented: 'Implementado', rejected: 'Rechazado' };
    return map[s] || s;
};

const typeLabel = (t: string) => t === 'article' ? 'Artículo sugerido' : 'Pregunta';

const respondingId = ref<number | null>(null);
const responseForm = useForm({ status: 'reviewed', admin_response: '' });

const openResponse = (s: any) => {
    respondingId.value = s.id;
    responseForm.status = s.status || 'reviewed';
    responseForm.admin_response = s.admin_response || '';
};

const submitResponse = () => {
    if (!respondingId.value) return;
    responseForm.put(route('admin.help.suggestions.respond', respondingId.value), {
        onSuccess: () => { respondingId.value = null; responseForm.reset(); },
    });
};
</script>

<template>
    <AppLayout title="Sugerencias de Ayuda">
        <div class="space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">Sugerencias y Preguntas</h1>

            <div class="space-y-4">
                <Card v-for="s in suggestions.data" :key="s.id">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900">{{ s.subject }}</h3>
                                <Badge :color="statusColor(s.status) as any">{{ statusLabel(s.status) }}</Badge>
                                <Badge color="blue">{{ typeLabel(s.type) }}</Badge>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">{{ s.user?.name }} &middot; {{ new Date(s.created_at).toLocaleDateString() }}</p>
                            <p class="mt-2 text-sm text-gray-700">{{ s.description }}</p>
                            <div v-if="s.admin_response" class="mt-3 rounded-lg bg-gray-50 p-3">
                                <p class="text-xs font-medium text-gray-500">Respuesta de {{ s.reviewer?.name }}:</p>
                                <p class="mt-1 text-sm text-gray-700">{{ s.admin_response }}</p>
                            </div>
                        </div>
                        <Button variant="secondary" @click="openResponse(s)">Responder</Button>
                    </div>

                    <div v-if="respondingId === s.id" class="mt-4 border-t border-gray-200 pt-4">
                        <form @submit.prevent="submitResponse" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select v-model="responseForm.status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm">
                                    <option value="pending">Pendiente</option>
                                    <option value="reviewed">Revisado</option>
                                    <option value="implemented">Implementado</option>
                                    <option value="rejected">Rechazado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Respuesta</label>
                                <textarea v-model="responseForm.admin_response" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" placeholder="Escribe tu respuesta..." />
                            </div>
                            <div class="flex gap-2">
                                <Button type="submit" :disabled="responseForm.processing">Guardar</Button>
                                <Button variant="secondary" @click="respondingId = null">Cancelar</Button>
                            </div>
                        </form>
                    </div>
                </Card>
            </div>

            <Pagination v-if="suggestions.data.length > 0" :links="suggestions.links" :current-page="suggestions.current_page" :last-page="suggestions.last_page" :total="suggestions.total" />
        </div>
    </AppLayout>
</template>
