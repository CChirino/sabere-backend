<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: any[];
    currentPage: number;
    lastPage: number;
    total: number;
}>();
</script>

<template>
    <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200">
        <div class="flex-1 flex justify-between sm:hidden">
            <Link
                v-if="currentPage > 1"
                :href="links[0].url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Anterior
            </Link>
            <Link
                v-if="currentPage < lastPage"
                :href="links[links.length - 1].url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Siguiente
            </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                            link.active
                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                            index === 0 ? 'rounded-l-md' : '',
                            index === links.length - 1 ? 'rounded-r-md' : '',
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>
