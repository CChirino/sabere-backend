<script setup lang="ts">
interface Option {
    value: string | number;
    label: string;
}

withDefaults(defineProps<{
    modelValue?: string | number;
    options: Option[];
    placeholder?: string;
    disabled?: boolean;
}>(), {
    disabled: false,
});

defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change'): void;
}>();
</script>

<template>
    <select
        :value="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value); $emit('change')"
        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
    >
        <option v-if="placeholder" value="">{{ placeholder }}</option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
        >
            {{ option.label }}
        </option>
    </select>
</template>
