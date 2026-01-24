<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

type RoleType = 'admin' | 'coordinator' | 'teacher' | 'student' | 'guardian';

const selectedRole = ref<RoleType | null>(null);
const isLoggingIn = ref(false);

const roles = [
    {
        id: 'admin' as RoleType,
        name: 'Administrador',
        description: 'Gestión completa de la institución',
        icon: 'shield',
        color: 'purple',
        email: 'admin@sabere.com',
        features: [
            { name: 'Gestión de Usuarios', description: 'Crear, editar y administrar usuarios del sistema' },
            { name: 'Períodos Académicos', description: 'Configurar años escolares y lapsos' },
            { name: 'Grados y Secciones', description: 'Organizar la estructura académica' },
            { name: 'Materias', description: 'Definir el pensum de estudios' },
            { name: 'Inscripciones', description: 'Matricular estudiantes en secciones' },
            { name: 'Asignaciones', description: 'Asignar profesores a materias y secciones' },
            { name: 'Horarios', description: 'Crear y gestionar horarios de clases' },
            { name: 'Reportes', description: 'Estadísticas y reportes institucionales' },
        ]
    },
    {
        id: 'coordinator' as RoleType,
        name: 'Coordinador',
        description: 'Supervisión académica',
        icon: 'clipboard',
        color: 'indigo',
        email: 'coordinador@sabere.com',
        features: [
            { name: 'Gestión de Profesores', description: 'Supervisar y gestionar el equipo docente' },
            { name: 'Seguimiento de Tareas', description: 'Monitorear tareas de todas las secciones' },
            { name: 'Seguimiento de Notas', description: 'Revisar progreso de calificaciones' },
            { name: 'Asignaciones', description: 'Gestionar asignación de materias' },
            { name: 'Reportes', description: 'Generar informes de rendimiento' },
            { name: 'Horarios', description: 'Supervisar horarios de clases' },
        ]
    },
    {
        id: 'teacher' as RoleType,
        name: 'Profesor',
        description: 'Herramientas para la enseñanza',
        icon: 'academic',
        color: 'teal',
        email: 'profesor@sabere.com',
        features: [
            { name: 'Mis Asignaciones', description: 'Ver materias y secciones asignadas' },
            { name: 'Calificaciones', description: 'Registrar notas por lapso' },
            { name: 'Asistencia', description: 'Control de asistencia diaria' },
            { name: 'Tareas', description: 'Crear y gestionar asignaciones' },
            { name: 'Chat de Sección', description: 'Comunicación con estudiantes' },
            { name: 'Reportes', description: 'Informes de rendimiento por sección' },
        ]
    },
    {
        id: 'student' as RoleType,
        name: 'Estudiante',
        description: 'Acceso a recursos académicos',
        icon: 'user',
        color: 'blue',
        email: 'estudiante@sabere.com',
        features: [
            { name: 'Mi Horario', description: 'Ver horario de clases semanal' },
            { name: 'Mis Notas', description: 'Consultar calificaciones por lapso' },
            { name: 'Tareas', description: 'Ver y entregar asignaciones' },
            { name: 'Chat de Sección', description: 'Comunicación con compañeros y profesores' },
        ]
    },
    {
        id: 'guardian' as RoleType,
        name: 'Representante',
        description: 'Seguimiento de representados',
        icon: 'users',
        color: 'orange',
        email: 'representante@sabere.com',
        features: [
            { name: 'Mis Representados', description: 'Ver lista de estudiantes a cargo' },
            { name: 'Notas', description: 'Consultar calificaciones de cada estudiante' },
            { name: 'Tareas', description: 'Ver asignaciones pendientes y entregadas' },
            { name: 'Asistencia', description: 'Historial de asistencia' },
        ]
    }
];

const currentRole = computed(() => roles.find(r => r.id === selectedRole.value));

const getColorClasses = (color: string, type: 'bg' | 'text' | 'border' | 'ring') => {
    const colors: Record<string, Record<string, string>> = {
        purple: { bg: 'bg-purple-500', text: 'text-purple-400', border: 'border-purple-500', ring: 'ring-purple-500/20' },
        indigo: { bg: 'bg-indigo-500', text: 'text-indigo-400', border: 'border-indigo-500', ring: 'ring-indigo-500/20' },
        teal: { bg: 'bg-teal-500', text: 'text-teal-400', border: 'border-teal-500', ring: 'ring-teal-500/20' },
        blue: { bg: 'bg-blue-500', text: 'text-blue-400', border: 'border-blue-500', ring: 'ring-blue-500/20' },
        orange: { bg: 'bg-orange-500', text: 'text-orange-400', border: 'border-orange-500', ring: 'ring-orange-500/20' },
    };
    return colors[color]?.[type] || '';
};

const loginForm = useForm({
    email: '',
    password: 'password',
    remember: true,
});

const loginAsRole = (email: string) => {
    isLoggingIn.value = true;
    loginForm.email = email;
    loginForm.post(route('login'), {
        onFinish: () => {
            isLoggingIn.value = false;
        },
    });
};
</script>

<template>
    <Head title="Demo - Saberé" />
    
    <div class="min-h-screen bg-slate-900">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-slate-900/95 backdrop-blur-sm border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 lg:h-20">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-teal-400 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">Saberé</span>
                    </Link>

                    <div class="flex items-center gap-4">
                        <Link href="/" class="text-slate-300 hover:text-white font-medium transition-colors">
                            ← Volver al inicio
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="pt-28 pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Title -->
                <div class="text-center mb-12">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-800 text-teal-400 text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Demo Interactiva
                    </span>
                    
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 text-white">
                        Explora Saberé según tu <span class="text-teal-400">rol</span>
                    </h1>
                    
                    <p class="text-lg text-slate-400 max-w-2xl mx-auto">
                        Selecciona un perfil para descubrir las funcionalidades disponibles para cada tipo de usuario en nuestra plataforma.
                    </p>
                </div>

                <!-- Role Selector -->
                <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-12">
                    <button
                        v-for="role in roles"
                        :key="role.id"
                        @click="selectedRole = role.id"
                        :class="[
                            'relative p-6 rounded-2xl border-2 transition-all duration-300 text-left group',
                            selectedRole === role.id 
                                ? `${getColorClasses(role.color, 'border')} bg-slate-800 ring-4 ${getColorClasses(role.color, 'ring')}`
                                : 'border-slate-700 bg-slate-800/50 hover:border-slate-600 hover:bg-slate-800'
                        ]"
                    >
                        <div :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-colors',
                            selectedRole === role.id ? `${getColorClasses(role.color, 'bg')}/20` : 'bg-slate-700'
                        ]">
                            <!-- Shield Icon (Admin) -->
                            <svg v-if="role.icon === 'shield'" :class="['w-6 h-6', selectedRole === role.id ? getColorClasses(role.color, 'text') : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <!-- Clipboard Icon (Coordinator) -->
                            <svg v-if="role.icon === 'clipboard'" :class="['w-6 h-6', selectedRole === role.id ? getColorClasses(role.color, 'text') : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <!-- Academic Icon (Teacher) -->
                            <svg v-if="role.icon === 'academic'" :class="['w-6 h-6', selectedRole === role.id ? getColorClasses(role.color, 'text') : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                            </svg>
                            <!-- User Icon (Student) -->
                            <svg v-if="role.icon === 'user'" :class="['w-6 h-6', selectedRole === role.id ? getColorClasses(role.color, 'text') : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <!-- Users Icon (Guardian) -->
                            <svg v-if="role.icon === 'users'" :class="['w-6 h-6', selectedRole === role.id ? getColorClasses(role.color, 'text') : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        
                        <h3 :class="['text-lg font-bold mb-1', selectedRole === role.id ? 'text-white' : 'text-slate-200']">
                            {{ role.name }}
                        </h3>
                        <p class="text-sm text-slate-400">{{ role.description }}</p>
                        
                        <!-- Selected indicator -->
                        <div v-if="selectedRole === role.id" :class="['absolute top-4 right-4 w-6 h-6 rounded-full flex items-center justify-center', getColorClasses(role.color, 'bg')]">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Features Display -->
                <transition
                    enter-active-class="transition-all duration-500 ease-out"
                    enter-from-class="opacity-0 translate-y-8"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-300 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-8"
                    mode="out-in"
                >
                    <div v-if="currentRole" :key="currentRole.id" class="bg-slate-800 rounded-3xl p-8 lg:p-12">
                        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                            <!-- Left: Features List -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-6">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', `${getColorClasses(currentRole.color, 'bg')}/20`]">
                                        <svg v-if="currentRole.icon === 'shield'" :class="['w-5 h-5', getColorClasses(currentRole.color, 'text')]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        <svg v-if="currentRole.icon === 'clipboard'" :class="['w-5 h-5', getColorClasses(currentRole.color, 'text')]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                        </svg>
                                        <svg v-if="currentRole.icon === 'academic'" :class="['w-5 h-5', getColorClasses(currentRole.color, 'text')]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                                        </svg>
                                        <svg v-if="currentRole.icon === 'user'" :class="['w-5 h-5', getColorClasses(currentRole.color, 'text')]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <svg v-if="currentRole.icon === 'users'" :class="['w-5 h-5', getColorClasses(currentRole.color, 'text')]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-white">{{ currentRole.name }}</h2>
                                        <p class="text-slate-400">{{ currentRole.description }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div 
                                        v-for="(feature, index) in currentRole.features" 
                                        :key="index"
                                        :class="['p-4 rounded-xl border-l-4 bg-slate-700/50', getColorClasses(currentRole.color, 'border')]"
                                    >
                                        <h4 class="font-semibold text-white mb-1">{{ feature.name }}</h4>
                                        <p class="text-sm text-slate-400">{{ feature.description }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right: Preview Mockup -->
                            <div class="lg:w-[400px] flex-shrink-0">
                                <div class="bg-slate-700/50 rounded-2xl p-3 border border-slate-600/50">
                                    <div class="bg-slate-800 rounded-xl overflow-hidden">
                                        <!-- Window Header -->
                                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-700">
                                            <div class="flex gap-1.5">
                                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                            </div>
                                            <div class="flex-1 text-center">
                                                <span class="text-xs text-slate-500">sabere.io/{{ currentRole.id }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="p-4 min-h-[300px]">
                                            <!-- Admin Preview -->
                                            <template v-if="currentRole.id === 'admin'">
                                                <div class="flex items-center justify-between mb-4">
                                                    <div class="text-white font-medium text-sm">Panel de Administración</div>
                                                    <span class="px-2 py-1 bg-purple-500/20 text-purple-400 text-xs rounded-full">Admin</span>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3 mb-4">
                                                    <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <div class="text-2xl font-bold text-white">248</div>
                                                        <div class="text-xs text-slate-400">Estudiantes</div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <div class="text-2xl font-bold text-white">18</div>
                                                        <div class="text-xs text-slate-400">Profesores</div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <div class="text-2xl font-bold text-white">12</div>
                                                        <div class="text-xs text-slate-400">Secciones</div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <div class="text-2xl font-bold text-white">8</div>
                                                        <div class="text-xs text-slate-400">Materias</div>
                                                    </div>
                                                </div>
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2 px-3 py-2 bg-purple-500/10 rounded-lg text-purple-400 text-xs">
                                                        <div class="w-2 h-2 rounded-full bg-purple-400"></div>
                                                        Gestión de Usuarios
                                                    </div>
                                                    <div class="flex items-center gap-2 px-3 py-2 bg-slate-700/50 rounded-lg text-slate-400 text-xs">
                                                        <div class="w-2 h-2 rounded-full bg-slate-500"></div>
                                                        Configuración Académica
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            <!-- Coordinator Preview -->
                                            <template v-if="currentRole.id === 'coordinator'">
                                                <div class="flex items-center justify-between mb-4">
                                                    <div class="text-white font-medium text-sm">Panel de Coordinación</div>
                                                    <span class="px-2 py-1 bg-indigo-500/20 text-indigo-400 text-xs rounded-full">Coordinador</span>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3 mb-4">
                                                    <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <div class="text-2xl font-bold text-white">18</div>
                                                        <div class="text-xs text-slate-400">Profesores</div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <div class="text-2xl font-bold text-yellow-400">12</div>
                                                        <div class="text-xs text-slate-400">Pendientes</div>
                                                    </div>
                                                </div>
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between px-3 py-2 bg-indigo-500/10 rounded-lg">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                                                            <span class="text-indigo-400 text-xs">Prof. García</span>
                                                        </div>
                                                        <span class="text-xs text-slate-400">3 materias</span>
                                                    </div>
                                                    <div class="flex items-center justify-between px-3 py-2 bg-slate-700/50 rounded-lg">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-2 h-2 rounded-full bg-green-400"></div>
                                                            <span class="text-slate-300 text-xs">Prof. Martínez</span>
                                                        </div>
                                                        <span class="text-xs text-slate-400">2 materias</span>
                                                    </div>
                                                    <div class="flex items-center justify-between px-3 py-2 bg-slate-700/50 rounded-lg">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                                                            <span class="text-slate-300 text-xs">Prof. López</span>
                                                        </div>
                                                        <span class="text-xs text-yellow-400">5 pendientes</span>
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            <!-- Teacher Preview -->
                                            <template v-if="currentRole.id === 'teacher'">
                                                <div class="flex items-center justify-between mb-4">
                                                    <div class="text-white font-medium text-sm">Mis Asignaciones</div>
                                                    <span class="px-2 py-1 bg-teal-500/20 text-teal-400 text-xs rounded-full">Profesor</span>
                                                </div>
                                                <div class="space-y-3">
                                                    <div class="bg-slate-700/50 rounded-lg p-3">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-white text-sm font-medium">Matemáticas</span>
                                                            <span class="text-xs text-slate-400">4to A</span>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <span class="px-2 py-1 bg-teal-500/20 text-teal-400 text-xs rounded">Notas</span>
                                                            <span class="px-2 py-1 bg-slate-600 text-slate-300 text-xs rounded">Asistencia</span>
                                                        </div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-3">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-white text-sm font-medium">Física</span>
                                                            <span class="text-xs text-slate-400">5to B</span>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <span class="px-2 py-1 bg-slate-600 text-slate-300 text-xs rounded">Notas</span>
                                                            <span class="px-2 py-1 bg-slate-600 text-slate-300 text-xs rounded">Tareas</span>
                                                        </div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-3">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-white text-sm font-medium">Química</span>
                                                            <span class="text-xs text-slate-400">3ro C</span>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <span class="px-2 py-1 bg-orange-500/20 text-orange-400 text-xs rounded">3 pendientes</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            <!-- Student Preview -->
                                            <template v-if="currentRole.id === 'student'">
                                                <div class="flex items-center justify-between mb-4">
                                                    <div class="text-white font-medium text-sm">Mi Horario</div>
                                                    <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded-full">Estudiante</span>
                                                </div>
                                                <div class="space-y-2 mb-4">
                                                    <div class="flex items-center gap-3 bg-slate-700/50 rounded-lg p-3">
                                                        <div class="text-xs text-slate-500 w-12">7:00</div>
                                                        <div class="flex-1 bg-blue-500/20 rounded px-3 py-2">
                                                            <div class="text-blue-400 text-xs font-medium">Matemáticas</div>
                                                            <div class="text-slate-500 text-xs">Prof. García</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3 bg-slate-700/50 rounded-lg p-3">
                                                        <div class="text-xs text-slate-500 w-12">8:30</div>
                                                        <div class="flex-1 bg-teal-500/20 rounded px-3 py-2">
                                                            <div class="text-teal-400 text-xs font-medium">Lengua</div>
                                                            <div class="text-slate-500 text-xs">Prof. Martínez</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3 bg-slate-700/50 rounded-lg p-3">
                                                        <div class="text-xs text-slate-500 w-12">10:00</div>
                                                        <div class="flex-1 bg-purple-500/20 rounded px-3 py-2">
                                                            <div class="text-purple-400 text-xs font-medium">Ciencias</div>
                                                            <div class="text-slate-500 text-xs">Prof. López</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <span class="text-xs text-slate-500">Lunes - 4to Grado "A"</span>
                                                </div>
                                            </template>
                                            
                                            <!-- Guardian Preview -->
                                            <template v-if="currentRole.id === 'guardian'">
                                                <div class="flex items-center justify-between mb-4">
                                                    <div class="text-white font-medium text-sm">Mis Representados</div>
                                                    <span class="px-2 py-1 bg-orange-500/20 text-orange-400 text-xs rounded-full">Representante</span>
                                                </div>
                                                <div class="space-y-3">
                                                    <div class="bg-slate-700/50 rounded-lg p-4">
                                                        <div class="flex items-center gap-3 mb-3">
                                                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">JR</div>
                                                            <div>
                                                                <div class="text-white text-sm font-medium">Juan Rodríguez</div>
                                                                <div class="text-xs text-slate-400">4to Grado "A"</div>
                                                            </div>
                                                        </div>
                                                        <div class="grid grid-cols-3 gap-2 text-center">
                                                            <div class="bg-slate-600/50 rounded p-2">
                                                                <div class="text-green-400 font-bold text-sm">18.5</div>
                                                                <div class="text-xs text-slate-500">Promedio</div>
                                                            </div>
                                                            <div class="bg-slate-600/50 rounded p-2">
                                                                <div class="text-blue-400 font-bold text-sm">95%</div>
                                                                <div class="text-xs text-slate-500">Asistencia</div>
                                                            </div>
                                                            <div class="bg-slate-600/50 rounded p-2">
                                                                <div class="text-orange-400 font-bold text-sm">2</div>
                                                                <div class="text-xs text-slate-500">Tareas</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="bg-slate-700/50 rounded-lg p-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-full bg-pink-500 flex items-center justify-center text-white font-bold">MR</div>
                                                            <div>
                                                                <div class="text-white text-sm font-medium">María Rodríguez</div>
                                                                <div class="text-xs text-slate-400">2do Grado "B"</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CTA -->
                        <div class="mt-8 pt-8 border-t border-slate-700">
                            <!-- Credenciales de prueba -->
                            
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <p class="text-slate-400">
                                    ¿Listo para experimentar Saberé como <span :class="getColorClasses(currentRole.color, 'text')">{{ currentRole.name }}</span>?
                                </p>
                                <div class="flex gap-3">
                                    <button 
                                        @click="loginAsRole(currentRole.email)"
                                        :disabled="isLoggingIn"
                                        :class="[
                                            'inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all',
                                            getColorClasses(currentRole.color, 'bg'),
                                            'text-white hover:opacity-90 disabled:opacity-50'
                                        ]"
                                    >
                                        <svg v-if="isLoggingIn" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span v-if="isLoggingIn">Ingresando...</span>
                                        <span v-else>Probar como {{ currentRole.name }}</span>
                                        <svg v-if="!isLoggingIn" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Empty State -->
                <div v-if="!selectedRole" class="text-center py-16">
                    <div class="w-20 h-20 rounded-full bg-slate-800 flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Selecciona un rol para comenzar</h3>
                    <p class="text-slate-400">Haz clic en cualquiera de las tarjetas de arriba para explorar las funcionalidades</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-teal-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="text-slate-400 text-sm">© 2024 Saberé. Todos los derechos reservados.</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <a href="#" class="text-slate-400 hover:text-white text-sm transition-colors">Términos</a>
                        <a href="#" class="text-slate-400 hover:text-white text-sm transition-colors">Privacidad</a>
                        <a href="#" class="text-slate-400 hover:text-white text-sm transition-colors">Contacto</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
