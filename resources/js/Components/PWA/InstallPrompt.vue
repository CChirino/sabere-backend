<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const showPrompt = ref(false);
const showIOSPrompt = ref(false);
const deferredPrompt = ref<any>(null);

const isIOS = () => {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !(window as any).MSStream;
};

const isInStandaloneMode = () => {
    return window.matchMedia('(display-mode: standalone)').matches || 
           (window.navigator as any).standalone === true;
};

const handleBeforeInstallPrompt = (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e;
    
    // Verificar si ya se descartó el prompt antes
    const dismissed = localStorage.getItem('pwa-prompt-dismissed');
    if (!dismissed) {
        showPrompt.value = true;
    }
};

const installPWA = async () => {
    if (!deferredPrompt.value) return;
    
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    
    if (outcome === 'accepted') {
        console.log('PWA installed');
    }
    
    deferredPrompt.value = null;
    showPrompt.value = false;
};

const dismissPrompt = () => {
    showPrompt.value = false;
    showIOSPrompt.value = false;
    localStorage.setItem('pwa-prompt-dismissed', 'true');
};

const dismissForLater = () => {
    showPrompt.value = false;
    showIOSPrompt.value = false;
    // No guardar en localStorage para que vuelva a aparecer
};

onMounted(() => {
    // Si ya está instalada, no mostrar nada
    if (isInStandaloneMode()) return;
    
    // Verificar si ya se descartó
    const dismissed = localStorage.getItem('pwa-prompt-dismissed');
    if (dismissed) return;
    
    // Para Android/Chrome
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    
    // Para iOS (Safari)
    if (isIOS() && !isInStandaloneMode()) {
        setTimeout(() => {
            showIOSPrompt.value = true;
        }, 3000);
    }
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
});
</script>

<template>
    <!-- Android/Chrome Install Prompt -->
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-full"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-full"
    >
        <div 
            v-if="showPrompt" 
            class="fixed bottom-0 left-0 right-0 z-50 p-4 sm:p-6 sm:bottom-4 sm:left-4 sm:right-auto sm:max-w-sm"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-4 sm:p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-semibold text-gray-900">Instalar Saberé</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Instala la app para acceso rápido y una mejor experiencia
                            </p>
                        </div>
                        <button 
                            @click="dismissForLater"
                            class="flex-shrink-0 text-gray-400 hover:text-gray-500"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 flex gap-3">
                        <button
                            @click="dismissPrompt"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            No, gracias
                        </button>
                        <button
                            @click="installPWA"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-teal-500 rounded-xl hover:bg-teal-600 transition-colors flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Instalar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- iOS Install Instructions -->
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-full"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-full"
    >
        <div 
            v-if="showIOSPrompt" 
            class="fixed bottom-0 left-0 right-0 z-50 p-4"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden max-w-sm mx-auto">
                <div class="p-4 sm:p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-bold text-white">S</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-semibold text-gray-900">Instalar Saberé</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Añade esta app a tu pantalla de inicio
                            </p>
                        </div>
                        <button 
                            @click="dismissForLater"
                            class="flex-shrink-0 text-gray-400 hover:text-gray-500"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                            </div>
                            <span>1. Toca el botón <strong>Compartir</strong></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <span>2. Selecciona <strong>"Añadir a inicio"</strong></span>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button
                            @click="dismissPrompt"
                            class="w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            Entendido
                        </button>
                    </div>
                </div>
                
                <!-- Arrow pointing down for iOS -->
                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2">
                    <div class="w-4 h-4 bg-white border-r border-b border-gray-100 transform rotate-45"></div>
                </div>
            </div>
        </div>
    </Transition>
</template>
