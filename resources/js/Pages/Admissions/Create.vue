<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const processing = ref(false);
</script>

<template>
    <Head title="Nueva admisión" />
    <AppLayout>
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Nueva admisión</h1>
            <p class="text-gray-600">Formulario en construcción.</p>
        </div>
    </AppLayout>
</template>
