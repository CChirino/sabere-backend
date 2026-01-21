<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
        side?: 'left' | 'right';
        title?: string;
    }>(),
    {
        show: false,
        maxWidth: 'md',
        side: 'right',
        title: '',
    },
);

const emit = defineEmits(['close']);

watch(
    () => props.show,
    (value) => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);

const close = () => {
    emit('close');
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
    }[props.maxWidth];
});

const slideClass = computed(() => {
    return props.side === 'right' ? 'right-0' : 'left-0';
});

const enterFromClass = computed(() => {
    return props.side === 'right' ? 'translate-x-full' : '-translate-x-full';
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="relative z-50">
            <!-- Backdrop -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="close"
                />
            </Transition>

            <!-- Panel -->
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div
                        class="pointer-events-none fixed inset-y-0 flex max-w-full"
                        :class="slideClass"
                    >
                        <Transition
                            enter-active-class="transform transition ease-in-out duration-300"
                            :enter-from-class="enterFromClass"
                            enter-to-class="translate-x-0"
                            leave-active-class="transform transition ease-in-out duration-300"
                            leave-from-class="translate-x-0"
                            :leave-to-class="enterFromClass"
                        >
                            <div
                                v-show="show"
                                class="pointer-events-auto w-screen"
                                :class="maxWidthClass"
                            >
                                <div class="flex h-full flex-col overflow-y-auto bg-white shadow-xl">
                                    <!-- Header -->
                                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <h2 class="text-lg font-semibold text-gray-900">
                                                {{ title }}
                                            </h2>
                                            <button
                                                type="button"
                                                class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                @click="close"
                                            >
                                                <span class="sr-only">Cerrar</span>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <slot name="header-description" />
                                    </div>

                                    <!-- Content -->
                                    <div class="relative flex-1 px-4 py-6 sm:px-6">
                                        <slot />
                                    </div>

                                    <!-- Footer -->
                                    <div v-if="$slots.footer" class="border-t border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
                                        <slot name="footer" />
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
