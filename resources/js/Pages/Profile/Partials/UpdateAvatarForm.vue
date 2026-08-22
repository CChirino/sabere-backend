<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import InputError from '@/Components/InputError.vue';

const user = usePage().props.auth.user;

const form = useForm({
    avatar: null as File | null,
});

const preview = ref<string | null>(user.avatar_url || null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.avatar = target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const submitAvatar = () => {
    form.post(route('profile.avatar.upload'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const removeAvatar = () => {
    form.delete(route('profile.avatar.remove'), {
        preserveScroll: true,
        onSuccess: () => {
            preview.value = null;
        },
    });
};
</script>

<template>
    <Card title="Foto de Perfil" subtitle="Actualiza tu foto de perfil. Se aceptan imágenes JPG, PNG o WebP de hasta 2MB.">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
            <div class="relative">
                <div v-if="preview" class="h-24 w-24 overflow-hidden rounded-full">
                    <img :src="preview" alt="Avatar" class="h-full w-full object-cover" />
                </div>
                <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-200">
                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>

            <div class="flex-1 space-y-3">
                <div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                        @change="handleFileChange"
                    />
                    <InputError :message="form.errors.avatar" class="mt-2" />
                </div>

                <div class="flex gap-2">
                    <Button
                        v-if="form.avatar"
                        :disabled="form.processing"
                        @click="submitAvatar"
                    >
                        <span v-if="form.processing">Subiendo...</span>
                        <span v-else>Guardar foto</span>
                    </Button>

                    <Button
                        v-if="user.avatar"
                        variant="danger"
                        :disabled="form.processing"
                        @click="removeAvatar"
                    >
                        Eliminar foto
                    </Button>
                </div>
            </div>
        </div>
    </Card>
</template>
