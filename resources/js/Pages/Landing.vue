<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const mobileMenuOpen = ref(false);
</script>

<template>
    <Head title="Saberé - Sistema de Gestión Escolar" />
    
    <div class="min-h-screen" style="background-color: #F8F5F0;">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 lg:h-20">
                    <Link href="/" class="text-2xl font-bold" style="color: #1A2A42;">
                        Saberé
                    </Link>

                    <nav class="hidden lg:flex items-center space-x-8">
                        <a href="#caracteristicas" class="text-gray-600 hover:text-gray-900 font-medium">Características</a>
                        <a href="#modulos" class="text-gray-600 hover:text-gray-900 font-medium">Módulos</a>
                        <a href="#precios" class="text-gray-600 hover:text-gray-900 font-medium">Precios</a>
                        <a href="#contacto" class="text-gray-600 hover:text-gray-900 font-medium">Contacto</a>
                    </nav>

                    <div class="hidden lg:flex items-center space-x-4">
                        <Link v-if="canLogin" :href="route('login')" class="text-gray-700 hover:text-gray-900 font-medium">
                            Iniciar Sesión
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="text-white px-5 py-2.5 rounded-lg font-medium transition-all hover:opacity-90" style="background-color: #F97316;">
                            Solicitar Demo
                        </Link>
                    </div>

                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                        <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div v-if="mobileMenuOpen" class="lg:hidden py-4 border-t border-gray-100">
                    <nav class="flex flex-col space-y-4">
                        <a href="#caracteristicas" class="text-gray-600 font-medium" @click="mobileMenuOpen = false">Características</a>
                        <a href="#modulos" class="text-gray-600 font-medium" @click="mobileMenuOpen = false">Módulos</a>
                        <a href="#precios" class="text-gray-600 font-medium" @click="mobileMenuOpen = false">Precios</a>
                        <a href="#contacto" class="text-gray-600 font-medium" @click="mobileMenuOpen = false">Contacto</a>
                        <div class="pt-4 border-t border-gray-100 space-y-3">
                            <Link v-if="canLogin" :href="route('login')" class="block text-gray-700 font-medium">Iniciar Sesión</Link>
                            <Link v-if="canRegister" :href="route('register')" class="block text-white text-center py-2.5 rounded-lg font-medium" style="background-color: #F97316;">Solicitar Demo</Link>
                        </div>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <!-- Hero Section -->
            <section class="pt-28 lg:pt-32 pb-16 lg:pb-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                        <div>
                            
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6" style="color: #1A2A42;">
                                El sistema que tu
                                <span class="relative inline-block">
                                    <span class="relative z-10" style="color: #27C2A5;">colegio necesita</span>
                                    <span class="absolute bottom-2 left-0 w-full h-3 opacity-30" style="background-color: #27C2A5;"></span>
                                </span>
                            </h1>
                            
                            <p class="text-2xl font-semibold mb-4" style="color: #1A2A42;">
                                Notas. Horarios. Tareas. <span style="color: #27C2A5;">Todo conectado.</span>
                            </p>
                            
                            <p class="text-lg text-gray-600 mb-8 max-w-lg">
                                Conecta a docentes, estudiantes y representantes en una sola plataforma. Notas por lapsos, horarios visuales, tareas y seguimiento académico en tiempo real.
                            </p>
                            
                            <div class="flex flex-col sm:flex-row gap-4 mb-12">
                                <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center gap-2 text-white px-8 py-4 rounded-xl text-lg font-semibold transition-all hover:opacity-90" style="background-color: #27C2A5;">
                                    Comenzar Gratis
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </Link>
                                <button class="inline-flex items-center justify-center gap-2 bg-white px-8 py-4 rounded-xl text-lg font-semibold border-2 border-gray-200 hover:border-gray-300 transition-all" style="color: #1A2A42;">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    Ver Demo
                                </button>
                            </div>
                        </div>

                        <div class="relative hidden lg:block">
                            <div class="relative w-full max-w-lg mx-auto">
                                <div class="absolute top-0 right-0 w-72 h-72 rounded-full opacity-20" style="background-color: #27C2A5;"></div>
                                <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full opacity-20" style="background-color: #7854D2;"></div>
                                
                                <div class="absolute top-8 right-0 bg-white rounded-xl shadow-lg p-4 flex items-center gap-3 z-20">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #27C2A5;">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: #1A2A42;">Nuevo registro</div>
                                        <div class="text-xs text-gray-500">María García - 3°B</div>
                                    </div>
                                </div>

                                <div class="relative z-10 mx-auto py-16">
                                    <svg class="w-full h-auto" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="50" y="150" width="80" height="100" rx="4" fill="#1A2A42"/>
                                        <rect x="55" y="155" width="70" height="90" rx="2" fill="#27C2A5"/>
                                        <rect x="140" y="170" width="60" height="80" rx="4" fill="#7854D2"/>
                                        <rect x="200" y="140" width="150" height="100" rx="8" fill="#1A2A42"/>
                                        <rect x="210" y="150" width="130" height="80" rx="4" fill="#E8F5F2"/>
                                        <rect x="180" y="240" width="190" height="10" rx="2" fill="#374151"/>
                                        <path d="M280 80 L320 100 L280 120 L240 100 Z" fill="#1A2A42"/>
                                        <rect x="275" y="100" width="10" height="30" fill="#1A2A42"/>
                                        <circle cx="280" cy="135" r="5" fill="#F97316"/>
                                        <circle cx="100" cy="100" r="15" fill="#27C2A5" opacity="0.6"/>
                                        <circle cx="350" cy="180" r="10" fill="#7854D2" opacity="0.6"/>
                                        <rect x="320" y="60" width="20" height="20" rx="4" fill="#F97316" opacity="0.6"/>
                                    </svg>
                                </div>

                                <div class="absolute bottom-16 left-0 bg-white rounded-xl shadow-lg p-4 flex items-center gap-3 z-20">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: #FEF3C7;">
                                        <svg class="w-5 h-5" style="color: #F97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: #1A2A42;">Reporte listo</div>
                                        <div class="text-xs text-gray-500">Calificaciones 1 Lapso</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="caracteristicas" class="py-20 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <span class="inline-block px-4 py-2 rounded-full text-sm font-medium mb-4" style="background-color: rgba(39, 194, 165, 0.1); color: #27C2A5;">
                            Características
                        </span>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4" style="color: #1A2A42;">
                            Todo lo que necesitas para una <span style="color: #27C2A5;">gestión eficiente</span>
                        </h2>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                            Herramientas poderosas diseñadas específicamente para las necesidades de instituciones educativas modernas.
                        </p>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(39, 194, 165, 0.15);">
                                <svg class="w-7 h-7" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Notas por Lapsos</h3>
                            <p class="text-gray-600 text-sm">Sistema de calificaciones organizado por 3 lapsos. Docentes registran, estudiantes y padres consultan.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(120, 84, 210, 0.15);">
                                <svg class="w-7 h-7" style="color: #7854D2;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Horarios Visuales</h3>
                            <p class="text-gray-600 text-sm">Calendario visual con horarios por sección. Incluye recreos y descarga en PDF.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(39, 194, 165, 0.15);">
                                <svg class="w-7 h-7" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Tareas y Entregas</h3>
                            <p class="text-gray-600 text-sm">Docentes asignan tareas, estudiantes entregan y todo queda registrado con fechas.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(120, 84, 210, 0.15);">
                                <svg class="w-7 h-7" style="color: #7854D2;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Portal de Representantes</h3>
                            <p class="text-gray-600 text-sm">Los padres ven las notas y tareas de sus hijos en tiempo real desde cualquier dispositivo.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(39, 194, 165, 0.15);">
                                <svg class="w-7 h-7" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Grados y Secciones</h3>
                            <p class="text-gray-600 text-sm">Organiza tu institución por grados y secciones. Asigna estudiantes y docentes fácilmente.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(249, 115, 22, 0.15);">
                                <svg class="w-7 h-7" style="color: #F97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Materias y Asignaciones</h3>
                            <p class="text-gray-600 text-sm">Gestiona materias y asigna docentes a cada sección de forma organizada.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(249, 115, 22, 0.15);">
                                <svg class="w-7 h-7" style="color: #F97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Roles y Permisos</h3>
                            <p class="text-gray-600 text-sm">Cada usuario ve solo lo que necesita: Admin, Docente, Estudiante o Representante.</p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgba(39, 194, 165, 0.15);">
                                <svg class="w-7 h-7" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Períodos Académicos</h3>
                            <p class="text-gray-600 text-sm">Configura años escolares y períodos. Mantén el historial organizado por ciclos.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modules Section -->
            <section id="modulos" class="py-20" style="background-color: #F8F5F0;">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-16 items-start">
                        <div>
                            <span class="inline-block px-4 py-2 rounded-full text-sm font-medium mb-4" style="background-color: rgba(120, 84, 210, 0.1); color: #7854D2;">
                                Por Rol
                            </span>
                            <h2 class="text-3xl sm:text-4xl font-bold mb-6" style="color: #1A2A42;">
                                Cada usuario tiene <span class="italic" style="color: #27C2A5;">su propio espacio</span>
                            </h2>
                            <p class="text-lg text-gray-600 mb-8">
                                Docentes, estudiantes, representantes y administradores. Cada rol accede solo a lo que necesita, con una interfaz diseñada para sus tareas específicas.
                            </p>
                            <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center gap-2 text-white px-6 py-3 rounded-xl font-semibold transition-all hover:opacity-90" style="background-color: #27C2A5;">
                                Crear mi cuenta
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </Link>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-6">
                            <div class="bg-white p-6 rounded-2xl">
                                <div class="w-3 h-3 rounded-full mb-4" style="background-color: #27C2A5;"></div>
                                <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Para Docentes</h3>
                                <p class="text-sm text-gray-500 mb-4">Herramientas para el día a día</p>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Registrar notas por lapso
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Crear y asignar tareas
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ver entregas de estudiantes
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Consultar sus asignaciones
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-white p-6 rounded-2xl">
                                <div class="w-3 h-3 rounded-full mb-4" style="background-color: #7854D2;"></div>
                                <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Para Estudiantes</h3>
                                <p class="text-sm text-gray-500 mb-4">Todo en un solo lugar</p>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ver horario de clases
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Consultar notas por lapso
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ver tareas pendientes
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Descargar horario en PDF
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-white p-6 rounded-2xl">
                                <div class="w-3 h-3 rounded-full mb-4" style="background-color: #27C2A5;"></div>
                                <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Para Representantes</h3>
                                <p class="text-sm text-gray-500 mb-4">Seguimiento de sus hijos</p>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Ver notas de sus hijos
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Revisar tareas asignadas
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Múltiples hijos vinculados
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-white p-6 rounded-2xl">
                                <div class="w-3 h-3 rounded-full mb-4" style="background-color: #7854D2;"></div>
                                <h3 class="text-lg font-bold mb-2" style="color: #1A2A42;">Para Administradores</h3>
                                <p class="text-sm text-gray-500 mb-4">Control total del sistema</p>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Gestionar usuarios y roles
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Configurar grados y secciones
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Administrar períodos y materias
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 flex-shrink-0" style="color: #27C2A5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Crear horarios por sección
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section id="precios" class="py-20 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl sm:text-4xl font-bold mb-4" style="color: #1A2A42;">
                        ¿Listo para transformar tu institución?
                    </h2>
                    <p class="text-lg text-gray-600 mb-10">
                        Únete a más de 500 colegios que ya confían en Saberé. Prueba gratis por 30 días.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center gap-2 text-white px-8 py-4 rounded-xl text-lg font-semibold transition-all hover:opacity-90" style="background-color: #27C2A5;">
                            Comenzar gratis
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </Link>
                        <a href="mailto:contacto@sabere.com" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-lg font-semibold border-2 border-gray-300 hover:border-gray-400 transition-all" style="color: #1A2A42;">
                            Contactar ventas
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer id="contacto" style="background-color: #1A2A42;" class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #27C2A5;">
                                <span class="text-white font-bold text-lg">S</span>
                            </div>
                            <span class="text-xl font-bold text-white">Saberé</span>
                        </div>
                        <p class="text-gray-400 text-sm mb-6">
                            La plataforma de gestión escolar más moderna y completa. Transformamos la educación con tecnología.
                        </p>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                contacto@sabere.com
                            </div>
                            <div class="flex items-center gap-2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                +58 (212) 123-4567
                            </div>
                        </div>
                        <div class="flex gap-4 mt-6">
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Producto</h4>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#caracteristicas" class="text-gray-400 hover:text-white transition-colors">Características</a></li>
                            <li><a href="#modulos" class="text-gray-400 hover:text-white transition-colors">Módulos</a></li>
                            <li><a href="#precios" class="text-gray-400 hover:text-white transition-colors">Precios</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Actualizaciones</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Empresa</h4>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Sobre nosotros</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Blog</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Carreras</a></li>
                            <li><a href="#contacto" class="text-gray-400 hover:text-white transition-colors">Contacto</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Recursos</h4>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Centro de ayuda</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Documentación</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Webinars</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Casos de éxito</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-400 text-sm">
                        © {{ new Date().getFullYear() }} Saberé. Todos los derechos reservados.
                    </p>
                    <div class="flex gap-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacidad</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Términos</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Cookies</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
