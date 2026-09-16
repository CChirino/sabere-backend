<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useApi } from '@/composables/useApi';

const page = usePage();
const studentId = (page.props as any).studentId as number;
const { get } = useApi();
const data = ref<any>(null);

const fetchProfile = async () => {
    data.value = await get<any>(`/api/v1/students/${studentId}/profile`);
};

onMounted(fetchProfile);
</script>

<template>
    <Head title="Ficha del estudiante" />
    <AppLayout>
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Ficha integral</h1>
            <div v-if="data" class="rounded-lg bg-white p-6 shadow">
                <p class="font-semibold">Contacto de emergencia:</p>
                <p class="text-sm text-gray-600">{{ data.profile.emergency_contact_name }} — {{ data.profile.emergency_contact_phone }}</p>
            </div>
        </div>
    </AppLayout>
</template>
