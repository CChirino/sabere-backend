<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import LogoMark from '@/Components/LogoMark.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const page = usePage();
const flashError = computed(() => (page.props as any).flash?.error);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="flex min-h-screen bg-slate-900">
        <!-- Panel izquierdo: marca -->
        <div
            class="relative hidden lg:flex lg:w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-sabere-accent via-[#1E6E63] to-sabere-dark p-10"
        >
            <!-- Brillos decorativos -->
            <div
                class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-sabere-accent/30 blur-3xl"
            ></div>
            <div
                class="pointer-events-none absolute bottom-0 right-0 h-96 w-96 rounded-full bg-sabere-purple/20 blur-3xl"
            ></div>

            <!-- Logo -->
            <div class="relative flex items-center gap-2">
                <LogoMark class="h-10 w-10" />
                <span class="text-2xl font-bold tracking-tight text-white">Saberé</span>
            </div>

            <!-- Mensaje -->
            <div class="relative max-w-md">
                <h1 class="text-4xl xl:text-5xl font-bold leading-tight text-white">
                    Tu espacio de gestión escolar.
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-white/80">
                    Accede a tus clases, tareas, calificaciones y comunicados en un solo lugar,
                    con seguimiento claro para toda la comunidad educativa.
                </p>
            </div>

            <!-- Footer -->
            <p class="relative text-sm text-white/60">
                © {{ new Date().getFullYear() }} Saberé · Sistema de gestión escolar
            </p>
        </div>

        <!-- Panel derecho: formulario -->
        <div class="flex w-full lg:w-1/2 items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <!-- Logo (solo móvil) -->
                <div class="mb-8 flex items-center gap-2 lg:hidden">
                    <LogoMark class="h-10 w-10" />
                    <span class="text-2xl font-bold tracking-tight text-white">Saberé</span>
                </div>

                <h2 class="text-2xl font-bold text-white">Inicia sesión</h2>
                <p class="mt-1 text-sm text-slate-400">
                    Accede a tus clases, tareas y comunicados.
                </p>

                <div v-if="status" class="mt-4 rounded-lg bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-400">
                    {{ status }}
                </div>

                <div v-if="flashError" class="mt-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm font-medium text-red-400">
                    {{ flashError }}
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300">
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="tu@correo.com"
                            class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-800 text-white placeholder-slate-500 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300">
                            Contraseña
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-800 text-white placeholder-slate-500 shadow-sm focus:border-sabere-accent focus:ring-sabere-accent"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                name="remember"
                                class="rounded border-slate-600 bg-slate-800 text-sabere-accent shadow-sm focus:ring-sabere-accent"
                            />
                            <span class="text-sm text-slate-300">Recordarme</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-sabere-accent hover:text-teal-300"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-sabere-accent px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-teal-300 focus:outline-none focus:ring-2 focus:ring-sabere-accent focus:ring-offset-2 focus:ring-offset-slate-900 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Ingresar
                    </button>

                    <div class="flex items-center gap-3">
                        <div class="h-px flex-1 bg-slate-700"></div>
                        <span class="text-xs text-slate-500">o</span>
                        <div class="h-px flex-1 bg-slate-700"></div>
                    </div>

                    <a
                        :href="route('auth.google')"
                        class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24">
                            <path
                                fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"
                            />
                            <path
                                fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            />
                            <path
                                fill="#FBBC05"
                                d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18A10.97 10.97 0 0 0 1 12c0 1.77.43 3.45 1.18 4.94l3.66-2.84z"
                            />
                            <path
                                fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                            />
                        </svg>
                        Ingresar con Google
                    </a>
                </form>
            </div>
        </div>
    </div>
</template>
