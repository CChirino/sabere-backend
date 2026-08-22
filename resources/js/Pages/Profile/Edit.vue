<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateAvatarForm from './Partials/UpdateAvatarForm.vue';
import UpdatePersonalInformationForm from './Partials/UpdatePersonalInformationForm.vue';
import UpdateEmergencyContactForm from './Partials/UpdateEmergencyContactForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Mi Perfil" />

    <AppLayout title="Mi Perfil">
        <div class="space-y-6">
            <UpdateAvatarForm />

            <UpdatePersonalInformationForm />

            <UpdateEmergencyContactForm />

            <UpdatePasswordForm />

            <DeleteUserForm />
        </div>
    </AppLayout>
</template>
