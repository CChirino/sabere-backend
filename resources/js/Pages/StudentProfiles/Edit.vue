<script setup lang="ts">
import { ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const studentId = (page.props as any).studentId as number;
</script>

<template>
    <Head title="Editar ficha" />
    <AppLayout>
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Editar ficha</h1>
            <p class="text-gray-600">Formulario en construcción para el estudiante {{ studentId }}.</p>
        </div>
    </AppLayout>
</template>
