<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const showingNavigationDropdown = ref(false);
const showingSidebar = ref(true);

const { user, hasRole, roleLabel, isAdmin, isDirector, isCoordinator, isTeacher, isStudent, isGuardian, isStaff } = useAuth();

interface NavItem {
    name: string;
    href: string;
    routeName: string;
    icon: string;
    show: boolean;
}

const navigation = computed<NavItem[]>(() => [
    {
        name: 'Dashboard',
        href: route('dashboard'),
        routeName: 'dashboard',
        icon: 'home',
        show: true,
    },
    // Admin / Director
    {
        name: 'Usuarios',
        href: route('admin.users.index'),
        routeName: 'admin.users.*',
        icon: 'users',
        show: hasRole(['admin', 'director']),
    },
    {
        name: 'Roles',
        href: route('admin.roles.index'),
        routeName: 'admin.roles.*',
        icon: 'shield',
        show: isAdmin.value,
    },
    // Académico
    {
        name: 'Períodos Académicos',
        href: route('academic.periods.index'),
        routeName: 'academic.periods.*',
        icon: 'calendar',
        show: isStaff.value,
    },
    {
        name: 'Niveles y Grados',
        href: route('academic.grades.index'),
        routeName: 'academic.grades.*',
        icon: 'academic-cap',
        show: isStaff.value,
    },
    {
        name: 'Secciones',
        href: route('academic.sections.index'),
        routeName: 'academic.sections.*',
        icon: 'collection',
        show: isStaff.value,
    },
    {
        name: 'Materias',
        href: route('academic.subjects.index'),
        routeName: 'academic.subjects.*',
        icon: 'book-open',
        show: isStaff.value,
    },
    {
        name: 'Inscripciones',
        href: route('academic.enrollments.index'),
        routeName: 'academic.enrollments.*',
        icon: 'user-add',
        show: isStaff.value,
    },
    {
        name: 'Asignaciones',
        href: route('academic.assignments.index'),
        routeName: 'academic.assignments.*',
        icon: 'clipboard-list',
        show: isStaff.value,
    },
    {
        name: 'Horarios',
        href: route('academic.schedules.index'),
        routeName: 'academic.schedules.*',
        icon: 'clock',
        show: hasRole(['admin', 'director', 'coordinator', 'teacher']),
    },
    // Profesor
    {
        name: 'Mis Materias',
        href: route('teacher.assignments'),
        routeName: 'teacher.assignments',
        icon: 'book-open',
        show: isTeacher.value && !isStaff.value,
    },
    {
        name: 'Tareas',
        href: route('teacher.tasks.index'),
        routeName: 'teacher.tasks.*',
        icon: 'document-text',
        show: hasRole(['admin', 'director', 'coordinator', 'teacher']),
    },
    {
        name: 'Boleta',
        href: route('teacher.scores.index'),
        routeName: 'teacher.scores.*',
        icon: 'chart-bar',
        show: hasRole(['admin', 'director', 'coordinator', 'teacher']),
    },
    {
        name: 'Chat Secciones',
        href: route('teacher.chat.index'),
        routeName: 'teacher.chat.*',
        icon: 'chat',
        show: hasRole(['admin', 'director', 'coordinator', 'teacher']),
    },
    // Estudiante
    {
        name: 'Mi Horario',
        href: route('student.schedule'),
        routeName: 'student.schedule',
        icon: 'clock',
        show: isStudent.value,
    },
    {
        name: 'Mis Tareas',
        href: route('student.tasks'),
        routeName: 'student.tasks',
        icon: 'document-text',
        show: isStudent.value,
    },
    {
        name: 'Mi Boleta',
        href: route('student.scores'),
        routeName: 'student.scores',
        icon: 'chart-bar',
        show: isStudent.value,
    },
    {
        name: 'Chat de Sección',
        href: route('student.chat'),
        routeName: 'student.chat',
        icon: 'chat',
        show: isStudent.value,
    },
    // Representante
    {
        name: 'Mis Representados',
        href: route('guardian.students'),
        routeName: 'guardian.students',
        icon: 'users',
        show: isGuardian.value,
    },
]);

const visibleNavigation = computed(() => navigation.value.filter(item => item.show));

const isCurrentRoute = (routeName: string): boolean => {
    if (routeName.endsWith('*')) {
        return route().current(routeName.slice(0, -1) + '*') || false;
    }
    return route().current(routeName) || false;
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            :class="[
                showingSidebar ? 'translate-x-0' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-64 bg-sabere-dark transition-transform duration-300 ease-in-out lg:translate-x-0'
            ]"
        >
            <!-- Logo -->
            <div class="flex h-16 items-center justify-center border-b border-sabere-dark/50 px-4">
                <Link :href="route('dashboard')" class="flex items-center">
                    <span class="text-2xl font-bold text-white">Saberé</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3">
                <div class="space-y-1">
                    <Link
                        v-for="item in visibleNavigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            isCurrentRoute(item.routeName)
                                ? 'bg-sabere-accent/20 text-sabere-accent'
                                : 'text-gray-300 hover:bg-white/10 hover:text-white',
                            'group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors'
                        ]"
                    >
                        <!-- Icons -->
                        <svg
                            v-if="item.icon === 'home'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'users'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'shield'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'calendar'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'academic-cap'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'collection'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'book-open'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'clock'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'document-text'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'chart-bar'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'user-add'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'clipboard-list'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'chat'"
                            class="mr-3 h-5 w-5 flex-shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </div>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    @click="showingSidebar = !showingSidebar"
                >
                    <span class="sr-only">Abrir menú</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Page title slot -->
                    <div class="flex flex-1 items-center min-w-0">
                        <div class="w-full">
                            <slot name="header" />
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Role badge -->
                        <span class="hidden sm:inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-sm font-medium text-blue-800">
                            {{ roleLabel }}
                        </span>

                        <!-- Profile dropdown -->
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex items-center gap-x-2 rounded-full bg-white p-1.5 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                >
                                    <span class="sr-only">Abrir menú de usuario</span>
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <span class="hidden lg:flex lg:items-center">
                                        <span class="text-sm font-semibold leading-6 text-gray-900">
                                            {{ user.name }}
                                        </span>
                                        <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Mi Perfil
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Cerrar Sesión
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Flash messages -->
                    <div v-if="$page.props.flash?.success" class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ $page.props.flash.success }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="$page.props.flash?.error" class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ $page.props.flash.error }}</p>
                            </div>
                        </div>
                    </div>

                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile sidebar overlay -->
        <div
            v-if="showingSidebar"
            class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
            @click="showingSidebar = false"
        ></div>
    </div>
</template>
