<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    cedula: user.cedula || '',
    phone: user.phone || '',
    birth_date: user.birth_date || '',
    bio: user.bio || '',
    address: user.address || '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card title="Información Personal" subtitle="Actualiza tus datos personales y de contacto.">
        <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre completo</label>
                    <Input id="name" v-model="form.name" type="text" required autocomplete="name" />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</label>
                    <Input id="email" v-model="form.email" type="email" required autocomplete="username" />
                    <InputError :message="form.errors.email" class="mt-1" />
                </div>

                <div>
                    <label for="cedula" class="mb-1 block text-sm font-medium text-gray-700">Cédula de identidad</label>
                    <Input id="cedula" v-model="form.cedula" type="text" placeholder="V-12345678" />
                    <InputError :message="form.errors.cedula" class="mt-1" />
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">Teléfono</label>
                    <Input id="phone" v-model="form.phone" type="text" placeholder="+58 412-1234567" />
                    <InputError :message="form.errors.phone" class="mt-1" />
                </div>

                <div>
                    <label for="birth_date" class="mb-1 block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
                    <Input id="birth_date" v-model="form.birth_date" type="date" />
                    <InputError :message="form.errors.birth_date" class="mt-1" />
                </div>

                <div>
                    <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Dirección</label>
                    <Input id="address" v-model="form.address" type="text" placeholder="Dirección de residencia" />
                    <InputError :message="form.errors.address" class="mt-1" />
                </div>
            </div>

            <div>
                <label for="bio" class="mb-1 block text-sm font-medium text-gray-700">Biografía</label>
                <textarea
                    id="bio"
                    v-model="form.bio"
                    rows="3"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Breve descripción sobre ti..."
                    maxlength="1000"
                />
                <p class="mt-1 text-xs text-gray-500">{{ form.bio?.length || 0 }}/1000 caracteres</p>
                <InputError :message="form.errors.bio" class="mt-1" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="form.processing">Guardar cambios</Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600">Guardado correctamente.</p>
                </Transition>
            </div>
        </form>
    </Card>
</template>
