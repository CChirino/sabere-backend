<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const mobileMenuOpen = ref(false);
const contactSuccess = ref(false);

const page = usePage();
const flashSuccess = computed(() => (page.props as any).flash?.success);

const contactForm = useForm({
    name: '',
    institution: '',
    email: '',
    phone: '',
    message: ''
});

const submitContact = () => {
    contactForm.post(route('contact.send'), {
        preserveScroll: true,
        onSuccess: () => {
            contactSuccess.value = true;
            contactForm.reset();
            setTimeout(() => {
                contactSuccess.value = false;
            }, 5000);
        },
    });
};

onMounted(() => {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.scroll-animate').forEach(el => {
        observer.observe(el);
    });

    // Card hover effect with mouse tracking
    const cards = document.querySelectorAll<HTMLElement>('.card-hover-effect');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const mouseEvent = e as MouseEvent;
            const rect = card.getBoundingClientRect();
            const x = mouseEvent.clientX - rect.left;
            const y = mouseEvent.clientY - rect.top;
            
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
});
</script>

<style scoped>
.scroll-animate {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.scroll-animate.animate-in {
    opacity: 1;
    transform: translateY(0);
}

.scroll-animate-delay-1 {
    transition-delay: 0.1s;
}

.scroll-animate-delay-2 {
    transition-delay: 0.2s;
}

.scroll-animate-delay-3 {
    transition-delay: 0.3s;
}

/* Card hover effect with mouse tracking */
.card-hover-effect {
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    --mouse-x: 50%;
    --mouse-y: 50%;
}

.card-hover-effect::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 2px;
    background: radial-gradient(
        600px circle at var(--mouse-x) var(--mouse-y),
        rgba(20, 184, 166, 0.6),
        transparent 40%
    );
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.card-hover-effect:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.card-hover-effect:hover::before {
    opacity: 1;
}

/* Variant colors for different cards */
.card-hover-effect.card-purple::before {
    background: radial-gradient(
        600px circle at var(--mouse-x) var(--mouse-y),
        rgba(168, 85, 247, 0.6),
        transparent 40%
    );
}

.card-hover-effect.card-teal::before {
    background: radial-gradient(
        600px circle at var(--mouse-x) var(--mouse-y),
        rgba(20, 184, 166, 0.6),
        transparent 40%
    );
}

.card-hover-effect.card-orange::before {
    background: radial-gradient(
        600px circle at var(--mouse-x) var(--mouse-y),
        rgba(251, 146, 60, 0.6),
        transparent 40%
    );
}
</style>

<template>
    <Head title="Saberé - Sistema de Gestión Escolar" />
    
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

                    <nav class="hidden lg:flex items-center space-x-8">
                        <a href="#caracteristicas" class="text-slate-300 hover:text-white font-medium transition-colors">Características</a>
                        <a href="#contacto" class="text-slate-300 hover:text-white font-medium transition-colors">Contacto</a>
                        <a href="#recursos" class="text-slate-300 hover:text-white font-medium transition-colors">Recursos</a>
                    </nav>


                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-slate-300 hover:bg-slate-800">
                        <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div v-if="mobileMenuOpen" class="lg:hidden py-4 border-t border-slate-800">
                    <nav class="flex flex-col space-y-4">
                        <a href="#caracteristicas" class="text-slate-300 font-medium" @click="mobileMenuOpen = false">Características</a>
                        <a href="#contacto" class="text-slate-300 font-medium" @click="mobileMenuOpen = false">Contacto</a>
                        <a href="#recursos" class="text-slate-300 font-medium" @click="mobileMenuOpen = false">Recursos</a>
                        <div class="pt-4 border-t border-slate-800 space-y-3">
                            <Link v-if="canRegister" :href="route('register')" class="block text-slate-900 bg-teal-400 text-center py-2.5 rounded-lg font-medium">Agendar Demo</Link>
                        </div>
                    </nav>
                </div>
            </div>
        </header>

        <main>
            <!-- Hero Section -->
            <section class="pt-28 lg:pt-36 pb-16 lg:pb-24 bg-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-800 text-teal-400 text-sm font-medium mb-6">
                            <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                            Nueva versión disponible
                        </span>
                        
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 text-white">
                            El futuro de la gestión<br>
                            <span class="text-teal-400">educativa hoy.</span>
                        </h1>
                        
                        <p class="text-lg text-slate-400 mb-8 max-w-2xl mx-auto">
                            Simplifica la administración de tu institución con inteligencia y transparencia. Control total en una sola plataforma diseñada para crecer contigo.
                        </p>
                        
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link :href="route('demo')" class="inline-flex items-center justify-center gap-2 bg-teal-400 text-slate-900 px-8 py-4 rounded-xl text-lg font-semibold transition-all hover:bg-teal-300">
                                Probar Gratis
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </Link>
                            <button class="inline-flex items-center justify-center gap-2 bg-slate-800 text-white px-8 py-4 rounded-xl text-lg font-semibold border border-slate-700 hover:bg-slate-700 transition-all">
                                Agendar Demo
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Dashboard Mockup -->
                    <div class="relative max-w-5xl mx-auto">
                        <div class="bg-slate-700/50 rounded-2xl p-3 shadow-2xl border border-slate-600/50 backdrop-blur-sm">
                            <div class="bg-slate-800/80 rounded-xl overflow-hidden flex">
                                <!-- Sidebar -->
                                <div class="w-48 bg-slate-900/50 p-4 hidden sm:block">
                                    <div class="flex items-center gap-2 mb-6">
                                        <div class="w-8 h-8 rounded-lg bg-teal-400 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                            </svg>
                                        </div>
                                        <span class="text-white font-semibold text-sm">Saberé</span>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-teal-400/10 text-teal-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            </svg>
                                            <span class="text-xs">Dashboard</span>
                                        </div>
                                        <div class="flex items-center gap-2 px-3 py-2 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                            <span class="text-xs">Estudiantes</span>
                                        </div>
                                        <div class="flex items-center gap-2 px-3 py-2 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-xs">Horarios</span>
                                        </div>
                                    </div>
                                    
                                    <!-- User Card -->
                                    <div class="mt-8 p-3 bg-slate-800 rounded-lg">
                                        <div class="text-xs text-slate-500 mb-1">Bienvenido Admin</div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-teal-400"></div>
                                            <span class="text-white text-xs">María García</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Main Content -->
                                <div class="flex-1 p-4">
                                    <!-- Header -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <div class="text-white font-medium text-sm">Panel Institucional</div>
                                            <div class="text-slate-500 text-xs">Resumen general</div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 bg-teal-400/20 text-teal-400 text-xs rounded-full">En línea</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Chat/Messages Cards -->
                                    <div class="space-y-3">
                                        <div class="bg-slate-700/50 rounded-lg p-3">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-purple-400 flex-shrink-0"></div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-white text-xs font-medium">Prof. Rodríguez</span>
                                                        <span class="text-slate-500 text-xs">10:30 AM</span>
                                                    </div>
                                                    <p class="text-slate-400 text-xs">Las notas del 3er lapso ya están cargadas...</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-slate-700/50 rounded-lg p-3">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-teal-400 flex-shrink-0"></div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-white text-xs font-medium">Sistema</span>
                                                        <span class="text-slate-500 text-xs">09:15 AM</span>
                                                    </div>
                                                    <p class="text-slate-400 text-xs">Nuevo estudiante registrado en 4to grado</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-slate-700/50 rounded-lg p-3">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-orange-400 flex-shrink-0"></div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-white text-xs font-medium">Coordinación</span>
                                                        <span class="text-slate-500 text-xs">Ayer</span>
                                                    </div>
                                                    <p class="text-slate-400 text-xs">Recordatorio: Reunión de docentes mañana</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="caracteristicas" class="py-20 bg-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-left mb-16 scroll-animate">
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 text-white">
                            Todo lo que necesitas, <span class="text-teal-400">perfectamente organizado.</span>
                        </h2>
                        <p class="text-lg text-slate-400">
                            Diseñado para la simplicidad, construido para la escala institucional.
                        </p>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-8 scroll-animate">
                        <!-- Card Gestión Académica -->
                        <div class="bg-slate-800 rounded-3xl p-8 text-white card-hover-effect card-purple">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3">Gestión Académica</h3>
                            <p class="text-slate-400 mb-8">
                                Control total sobre calificaciones, asistencia y progreso curricular. Visualiza el rendimiento de toda tu institución en tiempo real con dashboards inteligentes.
                            </p>
                            <div class="flex items-end gap-4 h-28">
                                <div class="bg-teal-700 rounded-xl w-full h-14"></div>
                                <div class="bg-teal-600 rounded-xl w-full h-20"></div>
                                <div class="bg-teal-400 rounded-xl w-full h-28"></div>
                                <div class="bg-teal-500 rounded-xl w-full h-24"></div>
                            </div>
                        </div>

                        <!-- Card Seguridad -->
                        <div class="bg-slate-800 rounded-3xl p-8 card-hover-effect card-teal">
                            <div class="w-10 h-10 rounded-lg bg-teal-500/20 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3 text-white">Seguridad Avanzada</h3>
                            <p class="text-slate-400 mb-8">
                                Encriptación de grado bancario y protocolos de acceso multinivel. Protegemos los datos sensibles de tu comunidad educativa con los más altos estándares.
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 bg-slate-700 rounded-xl px-4 py-3">
                                    <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span class="text-slate-300">SSL de 256 bits</span>
                                </div>
                                <div class="flex items-center gap-3 bg-slate-700 rounded-xl px-4 py-3">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    <span class="text-slate-300">Autenticación MFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Features Grid -->
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8 scroll-animate">
                        <div class="bg-slate-800 rounded-2xl p-6 border-l-4 border-l-teal-400 card-hover-effect card-teal">
                            <div class="w-10 h-10 rounded-lg bg-teal-500/20 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-white mb-2">Horarios Inteligentes</h4>
                            <p class="text-sm text-slate-400">Motor de optimización automática que evita conflictos de aulas y docentes.</p>
                        </div>

                        <div class="bg-slate-800 rounded-2xl p-6 border-l-4 border-l-teal-400 card-hover-effect card-teal">
                            <div class="w-10 h-10 rounded-lg bg-teal-500/20 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-white mb-2">Transparencia Total</h4>
                            <p class="text-sm text-slate-400 mb-4">Canales directos entre administración, docentes y padres de familia.</p>
                            <!-- Progress bar -->
                            <div class="bg-slate-700 rounded-full h-2 overflow-hidden">
                                <div class="bg-teal-400 h-full w-3/4 rounded-full"></div>
                            </div>
                        </div>

                        <div class="bg-slate-800 rounded-2xl p-6 border-l-4 border-l-teal-400 card-hover-effect card-teal">
                            <div class="w-10 h-10 rounded-lg bg-teal-500/20 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-white mb-2">Validaciones Automáticas</h4>
                            <p class="text-sm text-slate-400">Audita y consolida de registros y documentos legales.</p>
                        </div>

                        <div class="bg-slate-800 rounded-2xl p-6 border-l-4 border-l-teal-400 card-hover-effect card-teal">
                            <div class="w-10 h-10 rounded-lg bg-teal-500/20 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-white mb-2">Exportación en un Clic</h4>
                            <p class="text-sm text-slate-400">Genera reportes oficiales y datos en PDF o Excel al instante.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Contact Section -->
            <section id="contacto" class="py-20 bg-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-16 items-start scroll-animate">
                        <div>
                            <h2 class="text-3xl sm:text-4xl font-bold mb-6">
                                <span class="text-white">¿Listo para transformar</span><br>
                                <span class="text-teal-400">tu institución?</span>
                            </h2>
                            <p class="text-lg text-slate-400 mb-8">
                                Nuestro equipo de especialistas te ayudará a configurar la solución perfecta para tus necesidades. Déjanos tus datos y nos pondremos en contacto en menos de 24 horas.
                            </p>
                            
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-teal-400/20 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-slate-500 text-sm">Escríbenos</div>
                                        <div class="text-white font-medium">contacto@sabereapp.com</div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <!-- Contact Form -->
                        <div class="bg-slate-800 rounded-2xl p-8">
                            <!-- Success Message -->
                            <div v-if="contactSuccess" class="mb-5 rounded-xl bg-teal-400/10 border border-teal-400/30 p-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-teal-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-teal-400 text-sm font-medium">¡Mensaje enviado correctamente! Nos pondremos en contacto pronto.</p>
                                </div>
                            </div>

                            <form @submit.prevent="submitContact" class="space-y-5">
                                <div class="grid sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-slate-400 text-sm mb-2">Nombre Completo</label>
                                        <input 
                                            v-model="contactForm.name"
                                            type="text" 
                                            placeholder="Ej. Juan Pérez"
                                            class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-teal-400 transition-colors"
                                        >
                                        <p v-if="contactForm.errors.name" class="mt-1 text-red-400 text-xs">{{ contactForm.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-400 text-sm mb-2">Institución Educativa</label>
                                        <input 
                                            v-model="contactForm.institution"
                                            type="text" 
                                            placeholder="Nombre del Colegio"
                                            class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-teal-400 transition-colors"
                                        >
                                        <p v-if="contactForm.errors.institution" class="mt-1 text-red-400 text-xs">{{ contactForm.errors.institution }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-slate-400 text-sm mb-2">Correo Electrónico</label>
                                        <input 
                                            v-model="contactForm.email"
                                            type="email" 
                                            placeholder="email@institucion.edu"
                                            class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-teal-400 transition-colors"
                                        >
                                        <p v-if="contactForm.errors.email" class="mt-1 text-red-400 text-xs">{{ contactForm.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-400 text-sm mb-2">Teléfono (opcional)</label>
                                        <input 
                                            v-model="contactForm.phone"
                                            type="tel" 
                                            placeholder="+58 412 1234567"
                                            class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-teal-400 transition-colors"
                                        >
                                        <p v-if="contactForm.errors.phone" class="mt-1 text-red-400 text-xs">{{ contactForm.errors.phone }}</p>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-slate-400 text-sm mb-2">Mensaje</label>
                                    <textarea 
                                        v-model="contactForm.message"
                                        rows="4" 
                                        placeholder="¿En qué podemos ayudarte?"
                                        class="w-full bg-slate-700 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-teal-400 transition-colors resize-none"
                                    ></textarea>
                                    <p v-if="contactForm.errors.message" class="mt-1 text-red-400 text-xs">{{ contactForm.errors.message }}</p>
                                </div>
                                
                                <button 
                                    type="submit"
                                    :disabled="contactForm.processing"
                                    class="w-full bg-teal-400 hover:bg-teal-300 text-slate-900 font-semibold py-4 rounded-2xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="contactForm.processing">Enviando...</span>
                                    <span v-else>Enviar Mensaje</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer id="recursos" class="bg-slate-950 py-16 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-12">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-teal-400 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-white">Saberé</span>
                        </div>
                        <p class="text-slate-400 text-sm mb-6 max-w-sm">
                            Transformando la educación con tecnología. Una plataforma integral para instituciones educativas modernas.
                        </p>
                        
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Plataforma</h4>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#caracteristicas" class="text-slate-400 hover:text-white transition-colors">Características</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Integraciones</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Seguridad</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Compañía</h4>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#" class="text-slate-400 hover:text-white transition-colors">Blog</a></li>
                            <li><a href="#contacto" class="text-slate-400 hover:text-white transition-colors">Contacto</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Redes</h4>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-slate-500 text-sm">
                        © {{ new Date().getFullYear() }} Saberé Technologies. Todos los derechos reservados.
                    </p>
                    <div class="flex gap-6 text-sm">
                        <a href="#" class="text-slate-500 hover:text-white transition-colors">Privacidad</a>
                        <a href="#" class="text-slate-500 hover:text-white transition-colors">Términos</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
