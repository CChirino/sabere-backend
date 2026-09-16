<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ record: any }>();
</script>

<template>
    <AppLayout>
        <Head title="Detalle de incidencia" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h1 class="text-2xl font-bold text-gray-900">Detalle de incidencia</h1>
            </div>
        </div>
    </AppLayout>
</template>
