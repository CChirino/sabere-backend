<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';

const page = usePage();
const admissionId = (page.props as any).admissionId as number;
const { get } = useApi();
const admission = ref<any>(null);

const fetchAdmission = async () => {
    admission.value = await get<any>(`/api/v1/admissions/${admissionId}`);
};

onMounted(fetchAdmission);
</script>

<template>
    <Head title="Detalle de admisión" />
    <AppLayout>
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Admisión</h1>
            <div v-if="admission" class="rounded-lg bg-white p-6 shadow">
                <p class="font-semibold">{{ admission.first_name }} {{ admission.last_name }}</p>
                <p class="text-sm text-gray-600">Estado: {{ admission.status }}</p>
            </div>
        </div>
    </AppLayout>
</template>
