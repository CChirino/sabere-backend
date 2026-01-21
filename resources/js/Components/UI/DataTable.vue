<script setup lang="ts" generic="T">
export interface Column<T> {
    key: keyof T | string;
    label: string;
    sortable?: boolean;
    class?: string;
}

defineProps<{
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    emptyMessage?: string;
}>();

defineEmits<{
    (e: 'row-click', item: T): void;
}>();

const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="String(column.key)"
                        scope="col"
                        :class="[
                            column.class,
                            'px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500'
                        ]"
                    >
                        {{ column.label }}
                    </th>
                    <th v-if="$slots.actions" scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Acciones</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-if="loading">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-6 py-12 text-center">
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
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-6 py-12 text-center text-gray-500">
                        {{ emptyMessage || 'No hay datos disponibles' }}
                    </td>
                </tr>
                <tr
                    v-else
                    v-for="(item, index) in data"
                    :key="index"
                    class="hover:bg-gray-50 cursor-pointer"
                    @click="$emit('row-click', item)"
                >
                    <td
                        v-for="column in columns"
                        :key="String(column.key)"
                        :class="[column.class, 'whitespace-nowrap px-6 py-4 text-sm text-gray-900']"
                    >
                        <slot :name="`cell-${String(column.key)}`" :item="item" :value="getNestedValue(item, String(column.key))">
                            {{ getNestedValue(item, String(column.key)) }}
                        </slot>
                    </td>
                    <td v-if="$slots.actions" class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                        <slot name="actions" :item="item" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
