<script setup lang="ts" generic="T">
import { computed, ref } from 'vue';

export interface Column<T> {
    key: keyof T | string;
    label: string;
    sortable?: boolean;
    class?: string;
    hideOnMobile?: boolean;
}

export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

const props = withDefaults(defineProps<{
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    emptyMessage?: string;
    pagination?: PaginationMeta | null;
    striped?: boolean;
}>(), {
    loading: false,
    emptyMessage: 'No hay datos disponibles',
    pagination: null,
    striped: true,
});

const emit = defineEmits<{
    (e: 'row-click', item: T): void;
    (e: 'page-change', page: number): void;
    (e: 'sort', key: string, direction: 'asc' | 'desc'): void;
}>();

const sortKey = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
};

const handleSort = (column: Column<T>) => {
    if (!column.sortable) return;
    
    const key = String(column.key);
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
    
    emit('sort', key, sortDirection.value);
};

const goToPage = (page: number) => {
    if (page < 1 || (props.pagination && page > props.pagination.last_page)) return;
    emit('page-change', page);
};

const visiblePages = computed(() => {
    if (!props.pagination) return [];
    
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const delta = 1;
    const range: number[] = [];
    
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }
    
    if (current - delta > 2) {
        range.unshift(-1);
    }
    if (current + delta < last - 1) {
        range.push(-1);
    }
    
    range.unshift(1);
    if (last > 1) {
        range.push(last);
    }
    
    return range;
});

const visibleColumns = computed(() => props.columns.filter(c => !c.hideOnMobile));
</script>

<template>
    <div class="w-full">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="String(column.key)"
                            scope="col"
                            :class="[
                                column.class,
                                column.sortable ? 'cursor-pointer hover:bg-gray-100 select-none' : '',
                                'px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500'
                            ]"
                            @click="handleSort(column)"
                        >
                            <div class="flex items-center gap-1">
                                <span>{{ column.label }}</span>
                                <template v-if="column.sortable">
                                    <svg
                                        v-if="sortKey === String(column.key)"
                                        class="w-4 h-4 transition-transform"
                                        :class="sortDirection === 'desc' ? 'rotate-180' : ''"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                </template>
                            </div>
                        </th>
                        <th v-if="$slots.actions" scope="col" class="relative px-4 py-3">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-if="loading">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-12 text-center">
                            <div class="flex items-center justify-center">
                                <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="ml-2 text-gray-500">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-else-if="data.length === 0">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <span>{{ emptyMessage }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="(item, index) in data"
                        :key="index"
                        :class="[
                            striped && index % 2 === 1 ? 'bg-gray-50' : '',
                            'hover:bg-gray-100 transition-colors cursor-pointer'
                        ]"
                        @click="$emit('row-click', item)"
                    >
                        <td
                            v-for="column in columns"
                            :key="String(column.key)"
                            :class="[column.class, 'px-4 py-3 text-sm text-gray-900']"
                        >
                            <slot :name="`cell-${String(column.key)}`" :item="item" :value="getNestedValue(item, String(column.key))">
                                {{ getNestedValue(item, String(column.key)) }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3 text-right text-sm font-medium">
                            <slot name="actions" :item="item" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
            <div v-if="loading" class="bg-white rounded-lg shadow p-6 text-center">
                <div class="flex items-center justify-center">
                    <svg class="h-8 w-8 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-gray-500">Cargando...</span>
                </div>
            </div>
            
            <div v-else-if="data.length === 0" class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <span>{{ emptyMessage }}</span>
                </div>
            </div>
            
            <div
                v-else
                v-for="(item, index) in data"
                :key="index"
                class="bg-white rounded-lg shadow p-4"
                @click="$emit('row-click', item)"
            >
                <slot name="mobile-card" :item="item" :columns="visibleColumns" :getValue="getNestedValue">
                    <!-- Primera columna como título principal -->
                    <div v-if="visibleColumns.length > 0" class="mb-2">
                        <div class="font-medium text-gray-900">
                            <slot :name="`cell-${String(visibleColumns[0].key)}`" :item="item" :value="getNestedValue(item, String(visibleColumns[0].key))">
                                {{ getNestedValue(item, String(visibleColumns[0].key)) }}
                            </slot>
                        </div>
                    </div>
                    <!-- Resto de columnas en formato compacto -->
                    <div v-if="visibleColumns.length > 1" class="space-y-1.5">
                        <div
                            v-for="column in visibleColumns.slice(1)"
                            :key="String(column.key)"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-gray-500">{{ column.label }}</span>
                            <span class="text-gray-900">
                                <slot :name="`cell-${String(column.key)}`" :item="item" :value="getNestedValue(item, String(column.key))">
                                    {{ getNestedValue(item, String(column.key)) }}
                                </slot>
                            </span>
                        </div>
                    </div>
                    <div v-if="$slots.actions" class="pt-3 mt-3 border-t border-gray-100 flex justify-end">
                        <slot name="actions" :item="item" />
                    </div>
                </slot>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 px-2">
            <div class="text-sm text-gray-500">
                Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
            </div>
            
            <div class="flex items-center gap-1">
                <button
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                
                <template v-for="(page, idx) in visiblePages" :key="idx">
                    <span v-if="page === -1" class="px-2 text-gray-400">...</span>
                    <button
                        v-else
                        @click="goToPage(page)"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-md',
                            page === pagination.current_page
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        {{ page }}
                    </button>
                </template>
                
                <button
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
