<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Setting } from '@/types';

interface GroupedSettings {
    [group: string]: Setting[];
}

const props = defineProps<{
    settings: GroupedSettings;
}>();

const activeGroup = ref<string>(Object.keys(props.settings)[0] ?? 'institution');
const activeSettings = computed(() => props.settings[activeGroup.value] ?? []);

const formData = reactive<{ [key: string]: string | number | boolean | File | null }>({});
const filePreviews = ref<{ [key: string]: string | null }>({});
const processing = ref(false);
const errors = ref<{ [key: string]: string }>({});

activeSettings.value.forEach((setting: Setting) => {
    formData[setting.key] = setting.value ?? '';
});

const groupLabels: Record<string, string> = {
    institution: 'Institución',
    academic: 'Académico',
    branding: 'Imagen institucional',
    pwa: 'PWA',
};

const inputType = (type: string): string => {
    switch (type) {
        case 'integer':
            return 'number';
        case 'boolean':
            return 'checkbox';
        case 'file':
            return 'file';
        default:
            return 'text';
    }
};

const handleFile = (event: Event, setting: Setting) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        formData[setting.key] = target.files[0];
        filePreviews.value[setting.key] = URL.createObjectURL(target.files[0]);
    }
};

const submit = () => {
    processing.value = true;
    let pending = activeSettings.value.length;

    activeSettings.value.forEach((setting: Setting) => {
        router.put(route('admin.settings.update'), {
            group: setting.group,
            key: setting.key,
            value: formData[setting.key] as any,
            type: setting.type,
        }, {
            preserveScroll: true,
            onFinish: () => {
                pending--;
                if (pending <= 0) processing.value = false;
            },
        });
    });
};
</script>

<template>
    <Head title="Configuración" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Configuración institucional</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <Card class="md:col-span-1">
                    <nav class="space-y-1">
                        <button
                            v-for="(_, group) in props.settings"
                            :key="group"
                            @click="activeGroup = String(group)"
                            :class="[
                                'w-full rounded-md px-3 py-2 text-left text-sm font-medium',
                                activeGroup === group
                                    ? 'bg-blue-100 text-blue-800'
                                    : 'text-gray-700 hover:bg-gray-100',
                            ]"
                        >
                            {{ groupLabels[group] ?? group }}
                        </button>
                    </nav>
                </Card>

                <Card class="md:col-span-3">
                    <form @submit.prevent="submit" class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ groupLabels[activeGroup] ?? activeGroup }}
                        </h2>

                        <div
                            v-for="setting in activeSettings"
                            :key="setting.key"
                            class="space-y-2"
                        >
                            <InputLabel :for="setting.key" :value="setting.description ?? setting.key" />

                            <template v-if="setting.type === 'boolean'">
                                <label class="flex items-center">
                                    <input
                                        :id="setting.key"
                                        type="checkbox"
                                        v-model="formData[setting.key]"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-600">Activado</span>
                                </label>
                            </template>

                            <template v-else-if="setting.type === 'file'">
                                <input
                                    :id="setting.key"
                                    type="file"
                                    @change="handleFile($event, setting)"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <img
                                    v-if="filePreviews[setting.key] || setting.value"
                                    :src="filePreviews[setting.key] ?? setting.value"
                                    class="mt-2 h-24 object-contain"
                                    alt="Vista previa"
                                />
                            </template>

                            <template v-else>
                                <TextInput
                                    :id="setting.key"
                                    :model-value="String(formData[setting.key] ?? '')"
                                    @update:model-value="formData[setting.key] = $event"
                                    :type="inputType(setting.type)"
                                    class="mt-1 block w-full"
                                />
                            </template>

                            <InputError :message="errors[setting.key]" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="processing" type="submit">
                                Guardar cambios
                            </PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
