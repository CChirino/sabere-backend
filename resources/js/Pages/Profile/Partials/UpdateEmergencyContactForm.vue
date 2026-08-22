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
    emergency_contact_name: user.emergency_contact_name || '',
    emergency_contact_phone: user.emergency_contact_phone || '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card title="Contacto de Emergencia" subtitle="Información de contacto en caso de emergencia.">
        <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="emergency_contact_name" class="mb-1 block text-sm font-medium text-gray-700">Nombre del contacto</label>
                    <Input id="emergency_contact_name" v-model="form.emergency_contact_name" type="text" placeholder="Nombre completo" />
                    <InputError :message="form.errors.emergency_contact_name" class="mt-1" />
                </div>

                <div>
                    <label for="emergency_contact_phone" class="mb-1 block text-sm font-medium text-gray-700">Teléfono del contacto</label>
                    <Input id="emergency_contact_phone" v-model="form.emergency_contact_phone" type="text" placeholder="+58 412-1234567" />
                    <InputError :message="form.errors.emergency_contact_phone" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="form.processing">Guardar contacto</Button>

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
