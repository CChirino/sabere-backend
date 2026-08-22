<script setup lang="ts">
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps<{ categories: any; suggestions: any[]; pendingSuggestions: number }>();

const showCategory = ref(false);
const showArticle = ref(false);
const editingCategory = ref<any>(null);
const editingArticle = ref<any>(null);

const catForm = useForm({ name: '', description: '', icon: '', sort_order: 0 });
const artForm = useForm({ category_id: '', title: '', content: '', role_target: 'all', sort_order: 0 });

const openCat = (c: any = null) => {
    editingCategory.value = c;
    catForm.name = c?.name || '';
    catForm.description = c?.description || '';
    catForm.icon = c?.icon || '';
    catForm.sort_order = c?.sort_order || 0;
    showCategory.value = true;
};

const openArt = (a: any = null, categoryId: string = '') => {
    editingArticle.value = a;
    artForm.category_id = a?.category_id ? String(a.category_id) : categoryId;
    artForm.title = a?.title || '';
    artForm.content = a?.content || '';
    artForm.role_target = a?.role_target || 'all';
    artForm.sort_order = a?.sort_order || 0;
    showArticle.value = true;
};

const submitCat = () => {
    if (editingCategory.value) {
        catForm.put(route('admin.help.categories.update', editingCategory.value.id), { onSuccess: () => { showCategory.value = false; editingCategory.value = null; } });
    } else {
        catForm.post(route('admin.help.categories.store'), { onSuccess: () => { showCategory.value = false; catForm.reset(); } });
    }
};

const submitArt = () => {
    if (editingArticle.value) {
        artForm.put(route('admin.help.articles.update', editingArticle.value.id), { onSuccess: () => { showArticle.value = false; editingArticle.value = null; } });
    } else {
        artForm.post(route('admin.help.articles.store'), { onSuccess: () => { showArticle.value = false; artForm.reset(); } });
    }
};

const destroyCat = (id: number) => { if (confirm('¿Eliminar categoría?')) router.delete(route('admin.help.categories.destroy', id)); };
const destroyArt = (id: number) => { if (confirm('¿Eliminar artículo?')) router.delete(route('admin.help.articles.destroy', id)); };
</script>

<template>
    <AppLayout title="Gestión de Ayuda">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div><h1 class="text-2xl font-bold text-gray-900">Centro de Ayuda - Admin</h1><p class="mt-1 text-sm text-gray-500">Gestiona categorías, artículos y sugerencias</p></div>
                <div class="flex gap-2">
                    <Link :href="route('admin.help.suggestions')" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Sugerencias <Badge v-if="pendingSuggestions > 0" color="purple">{{ pendingSuggestions }}</Badge>
                    </Link>
                    <Button @click="openCat()">+ Categoría</Button>
                </div>
            </div>

            <div v-for="cat in categories.data" :key="cat.id" class="space-y-3">
                <Card>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ cat.name }}</h3>
                            <Badge color="blue">{{ cat.articles_count }} artículos</Badge>
                            <Badge v-if="!cat.is_active" color="gray">Inactiva</Badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="secondary" @click="openArt(null, String(cat.id))">+ Artículo</Button>
                            <Button variant="secondary" @click="openCat(cat)">Editar</Button>
                            <Button variant="danger" @click="destroyCat(cat.id)">Eliminar</Button>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ cat.description }}</p>
                    <div v-if="cat.articles?.length" class="mt-4 space-y-2">
                        <div v-for="art in cat.articles" :key="art.id" class="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                            <div>
                                <Link :href="route('help.show', art.slug)" class="font-medium text-gray-900 hover:text-blue-600">{{ art.title }}</Link>
                                <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">
                                    <span>{{ art.views_count }} vistas</span>
                                    <span v-if="art.role_target && art.role_target !== 'all'">&middot; {{ art.role_target }}</span>
                                    <Badge v-if="!art.is_active" color="gray" class="text-xs">Inactivo</Badge>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="secondary" @click="openArt(art)">Editar</Button>
                                <Button variant="danger" @click="destroyArt(art.id)">Eliminar</Button>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <Pagination v-if="categories.data.length > 0" :links="categories.links" :current-page="categories.current_page" :last-page="categories.last_page" :total="categories.total" />
        </div>

        <SlideOver :show="showCategory" :title="editingCategory ? 'Editar Categoría' : 'Nueva Categoría'" @close="showCategory = false">
            <form @submit.prevent="submitCat" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">Nombre</label><input v-model="catForm.name" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><InputError :message="catForm.errors.name" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Descripción</label><textarea v-model="catForm.description" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Icono</label><input v-model="catForm.icon" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" placeholder="Ej: user, bell, clock" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Orden</label><input v-model="catForm.sort_order" type="number" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /></div>
                <div class="flex justify-end gap-2 pt-4"><Button variant="secondary" @click="showCategory = false">Cancelar</Button><Button :disabled="catForm.processing" type="submit">{{ catForm.processing ? 'Guardando...' : 'Guardar' }}</Button></div>
            </form>
        </SlideOver>

        <SlideOver :show="showArticle" :title="editingArticle ? 'Editar Artículo' : 'Nuevo Artículo'" @close="showArticle = false">
            <form @submit.prevent="submitArt" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700">Categoría</label><select v-model="artForm.category_id" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option v-for="c in categories.data" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700">Título</label><input v-model="artForm.title" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /><InputError :message="artForm.errors.title" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Contenido</label><textarea v-model="artForm.content" rows="8" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Rol objetivo</label><select v-model="artForm.role_target" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"><option value="all">Todos</option><option value="student">Estudiante</option><option value="teacher">Profesor</option><option value="guardian">Representante</option><option value="admin">Admin</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700">Orden</label><input v-model="artForm.sort_order" type="number" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" /></div>
                <div class="flex justify-end gap-2 pt-4"><Button variant="secondary" @click="showArticle = false">Cancelar</Button><Button :disabled="artForm.processing" type="submit">{{ artForm.processing ? 'Guardando...' : 'Guardar' }}</Button></div>
            </form>
        </SlideOver>
    </AppLayout>
</template>
