<script setup lang="ts">
const props = withDefaults(defineProps<{
    title?: string;
    subtitle?: string;
    padding?: boolean;
    noBg?: boolean;
}>(), {
    padding: true
});
</script>

<template>
    <div :class="['overflow-hidden shadow-sm sm:rounded-lg', noBg ? '' : 'bg-white']">
        <div v-if="title || $slots.header" class="border-b border-gray-200 px-6 py-4">
            <slot name="header">
                <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
            </slot>
        </div>
        <div :class="props.padding ? 'px-6 py-5' : ''">
            <slot />
        </div>
        <div v-if="$slots.footer" class="border-t border-gray-200 px-6 py-4 bg-gray-50">
            <slot name="footer" />
        </div>
    </div>
</template>
