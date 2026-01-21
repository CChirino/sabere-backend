<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Section {
    id: number;
    name: string;
    full_name: string;
    grade: string;
    academic_period: string;
    students_count: number;
}

defineProps<{
    sections: Section[];
}>();

const getAvatarColor = (id: number) => {
    const colors = [
        'bg-blue-500',
        'bg-green-500',
        'bg-yellow-500',
        'bg-purple-500',
        'bg-pink-500',
        'bg-indigo-500',
        'bg-red-500',
        'bg-teal-500',
    ];
    return colors[id % colors.length];
};
</script>

<template>
    <Head title="Chat de Secciones" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Chat de Secciones
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Empty state -->
                <div v-if="sections.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No tienes secciones asignadas</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Cuando tengas asignaciones de materias, podrás acceder al chat de cada sección.
                    </p>
                </div>

                <!-- Sections grid -->
                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="section in sections"
                        :key="section.id"
                        :href="route('teacher.chat.show', section.id)"
                        class="group relative bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-sabere-accent transition-all"
                    >
                        <div class="flex items-start gap-4">
                            <div :class="[getAvatarColor(section.id), 'w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg']">
                                {{ section.name }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-sabere-accent transition-colors">
                                    {{ section.full_name }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ section.academic_period }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ section.students_count }} estudiantes
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-sabere-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
