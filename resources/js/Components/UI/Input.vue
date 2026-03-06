<script setup lang="ts">
withDefaults(defineProps<{
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
}>(), {
    type: 'text',
    disabled: false,
    required: false,
});

defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();
</script>

<template>
    <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500"
    />
</template>
