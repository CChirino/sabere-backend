<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
}

interface Message {
    id: number;
    message: string;
    type: string;
    attachment_url: string | null;
    attachment_name: string | null;
    user: User;
    is_mine: boolean;
    created_at: string;
    time: string;
    date: string;
}

interface Section {
    id: number;
    name: string;
    full_name: string;
    grade: string;
    academic_period: string;
}

const props = defineProps<{
    section: Section;
    messages: Message[];
    students: User[];
    teachers: User[];
    currentUserId: number;
}>();

const messagesContainer = ref<HTMLElement | null>(null);
const messageInput = ref('');

const form = useForm({
    message: '',
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const sendMessage = () => {
    if (!form.message.trim()) return;
    
    const messageText = form.message;
    const now = new Date();
    
    // Agregar mensaje optimistamente a la lista local
    const optimisticMessage: Message = {
        id: Date.now(), // ID temporal
        message: messageText,
        type: 'text',
        attachment_url: null,
        attachment_name: null,
        user: {
            id: props.currentUserId,
            name: props.students.find(s => s.id === props.currentUserId)?.name || 
                  props.teachers?.find(t => t.id === props.currentUserId)?.name || 'Tú',
        },
        is_mine: true,
        created_at: now.toISOString(),
        time: now.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' }),
        date: now.toLocaleDateString('es-VE'),
    };
    
    localMessages.value.push(optimisticMessage);
    scrollToBottom();
    
    form.post(route('student.chat.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
        },
        onError: () => {
            // Remover mensaje optimista si falla
            const index = localMessages.value.findIndex(m => m.id === optimisticMessage.id);
            if (index > -1) {
                localMessages.value.splice(index, 1);
            }
        },
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getAvatarColor = (userId: number) => {
    const colors = [
        'bg-blue-500',
        'bg-green-500',
        'bg-yellow-500',
        'bg-purple-500',
        'bg-pink-500',
        'bg-indigo-500',
        'bg-red-500',
        'bg-teal-500',
    ];
    return colors[userId % colors.length];
};

const isTeacher = (userId: number) => {
    return props.teachers?.some(t => t.id === userId) || false;
};

// Todos los participantes (estudiantes + profesores)
const allParticipants = computed(() => {
    return [...props.students, ...(props.teachers || [])];
});

// Lista reactiva de mensajes para actualizaciones en tiempo real
const localMessages = ref<Message[]>([...props.messages]);

// Agrupar mensajes por fecha (usando localMessages)
const groupedMessages = computed(() => {
    const groups: { [key: string]: Message[] } = {};
    
    localMessages.value.forEach(msg => {
        if (!groups[msg.date]) {
            groups[msg.date] = [];
        }
        groups[msg.date].push(msg);
    });
    
    return groups;
});

onMounted(() => {
    scrollToBottom();
    
    // Suscribirse al canal de la sección para recibir mensajes en tiempo real
    if (window.Echo) {
        window.Echo.private(`section-chat.${props.section.id}`)
            .listen('.message.sent', (e: { message: Message }) => {
                // Agregar el mensaje a la lista local
                const newMessage = {
                    ...e.message,
                    is_mine: e.message.user.id === props.currentUserId,
                };
                localMessages.value.push(newMessage);
                scrollToBottom();
            });
    }
});

onUnmounted(() => {
    // Limpiar la suscripción al canal cuando se desmonte el componente
    if (window.Echo) {
        window.Echo.leave(`section-chat.${props.section.id}`);
    }
});
</script>

<template>
    <Head :title="`Chat - ${section.full_name}`" />

    <AppLayout>
        <div class="flex flex-col h-[calc(100vh-4rem)]">
            <!-- Header del chat -->
            <div class="bg-white border-b px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        Chat de {{ section.full_name }}
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ section.academic_period }} · {{ students.length }} estudiantes · {{ teachers?.length || 0 }} profesores
                    </p>
                </div>
                
                <!-- Indicador de participantes -->
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2">
                        <div
                            v-for="participant in allParticipants.slice(0, 5)"
                            :key="participant.id"
                            :class="[getAvatarColor(participant.id), 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium ring-2 ring-white']"
                            :title="participant.name"
                        >
                            {{ getInitials(participant.name) }}
                        </div>
                        <div
                            v-if="allParticipants.length > 5"
                            class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-xs font-medium ring-2 ring-white"
                        >
                            +{{ allParticipants.length - 5 }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área de mensajes -->
            <div
                ref="messagesContainer"
                class="flex-1 overflow-y-auto bg-gray-50 px-6 py-4"
            >
                <!-- Mensaje de bienvenida si no hay mensajes -->
                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-gray-500">
                    <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <p class="text-lg font-medium">¡Bienvenido al chat de tu sección!</p>
                    <p class="text-sm">Sé el primero en enviar un mensaje a tus compañeros.</p>
                </div>

                <!-- Mensajes agrupados por fecha -->
                <div v-else>
                    <div v-for="(msgs, date) in groupedMessages" :key="date" class="mb-6">
                        <!-- Separador de fecha -->
                        <div class="flex items-center justify-center mb-4">
                            <div class="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full">
                                {{ date }}
                            </div>
                        </div>

                        <!-- Mensajes del día -->
                        <div class="space-y-3">
                            <div
                                v-for="msg in msgs"
                                :key="msg.id"
                                :class="[
                                    'flex items-end gap-2',
                                    msg.is_mine ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <!-- Avatar (solo para mensajes de otros) -->
                                <div
                                    v-if="!msg.is_mine"
                                    :class="[getAvatarColor(msg.user.id), 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium flex-shrink-0']"
                                    :title="msg.user.name"
                                >
                                    {{ getInitials(msg.user.name) }}
                                </div>

                                <!-- Burbuja del mensaje -->
                                <div
                                    :class="[
                                        'max-w-[70%] rounded-2xl px-4 py-2',
                                        msg.is_mine
                                            ? 'bg-sabere-accent text-white rounded-br-md'
                                            : 'bg-white text-gray-900 rounded-bl-md shadow-sm'
                                    ]"
                                >
                                    <!-- Nombre del usuario (solo para mensajes de otros) -->
                                    <div v-if="!msg.is_mine" class="flex items-center gap-2 mb-1">
                                        <p class="text-xs font-semibold text-sabere-accent">
                                            {{ msg.user.name }}
                                        </p>
                                        <span v-if="isTeacher(msg.user.id)" class="text-xs bg-sabere-purple/20 text-sabere-purple px-1.5 py-0.5 rounded-full">
                                            Profesor
                                        </span>
                                    </div>
                                    
                                    <!-- Contenido del mensaje -->
                                    <p class="text-sm whitespace-pre-wrap break-words">{{ msg.message }}</p>
                                    
                                    <!-- Hora del mensaje -->
                                    <p :class="[
                                        'text-xs mt-1',
                                        msg.is_mine ? 'text-white/70' : 'text-gray-400'
                                    ]">
                                        {{ msg.time }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input para enviar mensaje -->
            <div class="bg-white border-t px-6 py-4">
                <form @submit.prevent="sendMessage" class="flex items-center gap-3">
                    <div class="flex-1 relative">
                        <input
                            v-model="form.message"
                            type="text"
                            placeholder="Escribe un mensaje..."
                            class="w-full px-4 py-3 bg-gray-100 border-0 rounded-full focus:ring-2 focus:ring-sabere-accent focus:bg-white transition-all"
                            :disabled="form.processing"
                            maxlength="1000"
                            @keydown.enter.prevent="sendMessage"
                        />
                    </div>
                    
                    <button
                        type="submit"
                        :disabled="!form.message.trim() || form.processing"
                        class="w-12 h-12 rounded-full bg-sabere-accent text-white flex items-center justify-center hover:bg-sabere-accent/90 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
                
                <p v-if="form.errors.message" class="text-red-500 text-xs mt-2">
                    {{ form.errors.message }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
