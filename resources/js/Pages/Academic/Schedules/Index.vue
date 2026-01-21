<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Section } from '@/types';

const sections = ref<Section[]>([]);
const loading = ref(true);

const fetchSections = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/v1/sections', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        sections.value = data.data || [];
    } catch (error) {
        console.error('Error fetching sections:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchSections();
});
</script>

<template>
    <Head title="Horarios" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Horarios por Sección</h1>
        </template>

        <!-- Main Container with background -->
        <Card :padding="false" class="overflow-hidden">
            <!-- Header inside card -->
            <div class="bg-gradient-to-r from-sabere-primary to-sabere-dark px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-white">
                        <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium text-black" >Selecciona una sección para ver o editar su horario</span>
                    </div>
                    <span class="text-white/70 text-sm">{{ sections.length }} secciones</span>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="p-12 text-center bg-gray-50">
                <div class="flex items-center justify-center">
                    <svg class="h-8 w-8 animate-spin text-sabere-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-gray-500">Cargando secciones...</span>
                </div>
            </div>

            <!-- Sections Grid -->
            <div v-else-if="sections.length > 0" class="p-6 bg-gray-50">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link
                        v-for="section in sections"
                        :key="section.id"
                        :href="route('academic.schedules.section', section.id)"
                        class="group block"
                    >
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 h-full hover:shadow-md hover:border-sabere-accent/50 transition-all duration-200 group-hover:-translate-y-0.5">
                            <div class="flex flex-col h-full">
                                <!-- Header -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-500">
                                            {{ section.grade?.name }}
                                        </p>
                                        <h3 class="text-xl font-bold text-sabere-primary mt-0.5">
                                            Sección {{ section.name }}
                                        </h3>
                                    </div>
                                    <Badge :color="section.status ? 'green' : 'gray'" class="ml-2 flex-shrink-0">
                                        {{ section.status ? 'Activa' : 'Inactiva' }}
                                    </Badge>
                                </div>
                                
                                <!-- Info -->
                                <div class="flex-1 space-y-2">
                                    <p class="text-sm text-gray-500">
                                        {{ section.grade?.education_level?.name }}
                                    </p>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                        {{ section.enrollments_count || 0 }} estudiantes
                                    </div>
                                </div>
                                
                                <!-- Action -->
                                <div class="mt-4 pt-3 border-t border-gray-100">
                                    <span class="inline-flex items-center text-sm font-medium text-sabere-accent group-hover:text-sabere-dark transition-colors">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Ver Horario
                                        <svg class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="p-12 text-center bg-gray-50">
                <div class="max-w-sm mx-auto">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No hay secciones</h3>
                    <p class="mt-1 text-gray-500">Crea secciones primero para gestionar horarios.</p>
                    <Link :href="route('academic.sections.index')" class="mt-4 inline-flex items-center text-sm font-medium text-sabere-accent hover:text-sabere-dark">
                        Ir a Secciones
                        <svg class="ml-1 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
