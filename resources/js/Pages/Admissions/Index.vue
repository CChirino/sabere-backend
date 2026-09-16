<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';

const { get } = useApi();
const admissions = ref<any[]>([]);

const fetchAdmissions = async () => {
    const response = await get<any>('/api/v1/admissions');
    admissions.value = response?.data ?? [];
};

onMounted(fetchAdmissions);
</script>

<template>
    <Head title="Admisiones" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Admisiones</h1>
                <Link :href="route('admissions.create')" class="rounded-md bg-sabere-accent px-4 py-2 text-sm font-semibold text-white hover:bg-sabere-accent/90">
                    Nueva admisión
                </Link>
            </div>

            <div class="space-y-3">
                <Link
                    v-for="admission in admissions"
                    :key="admission.id"
                    :href="route('admissions.show', admission.id)"
                    class="block rounded-lg bg-white p-4 shadow hover:bg-gray-50"
                >
                    <p class="font-semibold text-gray-900">{{ admission.first_name }} {{ admission.last_name }}</p>
                    <p class="text-sm text-gray-600">Cédula: {{ admission.id_number }} — Estado: {{ admission.status }}</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
