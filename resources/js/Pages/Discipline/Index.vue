<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const records = ref([]);
</script>

<template>
    <AppLayout>
        <Head title="Disciplina" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Disciplina</h1>
                    <Link :href="route('discipline.create')" class="rounded-md bg-sabere-accent px-4 py-2 text-sm font-semibold text-white hover:bg-sabere-accent/90">
                        Registrar incidencia
                    </Link>
                </div>

                <div class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                    Listado de incidencias.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
