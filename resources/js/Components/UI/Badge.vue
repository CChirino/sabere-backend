<script setup lang="ts">
defineProps<{
    color?: 'gray' | 'red' | 'yellow' | 'green' | 'blue' | 'indigo' | 'purple' | 'pink';
    size?: 'sm' | 'md' | 'lg';
}>();

const colorClasses = {
    gray: 'bg-gray-100 text-gray-800',
    red: 'bg-red-100 text-red-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    green: 'bg-green-100 text-green-800',
    blue: 'bg-blue-100 text-blue-800',
    indigo: 'bg-indigo-100 text-indigo-800',
    purple: 'bg-purple-100 text-purple-800',
    pink: 'bg-pink-100 text-pink-800',
};

const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-0.5 text-sm',
    lg: 'px-3 py-1 text-sm',
};
</script>

<template>
    <span
        :class="[
            colorClasses[color || 'gray'],
            sizeClasses[size || 'md'],
            'inline-flex items-center rounded-full font-medium'
        ]"
    >
        <slot />
    </span>
</template>
