<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);
let pollInterval = null;

const fetchNotifications = async () => {
    try {
        const { data } = await axios.get('/notifications');
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (e) {
        console.error('Error fetching notifications:', e);
    }
};

const togglePanel = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && notifications.value.length === 0) {
        fetchNotifications();
    }
};

const closePanel = () => {
    isOpen.value = false;
};

const markAsRead = async (notification) => {
    if (!notification.is_read) {
        try {
            await axios.post(`/notifications/${notification.id}/read`);
            notification.is_read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e) {
            console.error(e);
        }
    }
    if (notification.action_url) {
        closePanel();
        router.visit(notification.action_url);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/read-all');
        notifications.value.forEach(n => n.is_read = true);
        unreadCount.value = 0;
    } catch (e) {
        console.error(e);
    }
};

const urgencyConfig = {
    high: {
        dot: 'bg-red-500',
        border: 'border-red-500/20',
        bg: 'bg-red-500/5',
        icon: 'md-warningamber-outlined',
        label: 'Alta',
    },
    medium: {
        dot: 'bg-amber-500',
        border: 'border-amber-500/20',
        bg: 'bg-amber-500/5',
        icon: 'ri-information-line',
        label: 'Media',
    },
    info: {
        dot: 'bg-emerald-500',
        border: 'border-emerald-500/20',
        bg: 'bg-emerald-500/5',
        icon: 'ri-checkbox-circle-line',
        label: 'Info',
    },
};

const getConfig = (urgency) => urgencyConfig[urgency] || urgencyConfig.info;

const groupedNotifications = computed(() => {
    const groups = { high: [], medium: [], info: [] };
    notifications.value.forEach(n => {
        if (groups[n.urgency]) groups[n.urgency].push(n);
    });
    return groups;
});

const groupLabels = {
    high: 'Urgentes',
    medium: 'Atención',
    info: 'Informativas',
};

onMounted(() => {
    fetchNotifications();
    // Poll every 60 seconds
    pollInterval = setInterval(fetchNotifications, 60000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="relative">
        <!-- Bell Button -->
        <button
            @click="togglePanel"
            class="relative p-2 rounded-xl text-[#a1a1aa] hover:text-white hover:bg-[#262626] transition-all"
            title="Centro de Alertas"
        >
            <v-icon name="md-notificationsactive-outlined" scale="1.1" />
            <Transition name="badge">
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center px-1 animate-pulse shadow-lg shadow-red-500/30"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Transition>
        </button>

        <!-- Backdrop -->
        <Transition name="fade">
            <div v-if="isOpen" @click="closePanel" class="fixed inset-0 z-40"></div>
        </Transition>

        <!-- Dropdown Panel -->
        <Transition name="slide-down">
            <div
                v-if="isOpen"
                class="absolute right-0 top-12 w-[420px] max-h-[520px] bg-[#18181a] border border-[#2a2a2a] rounded-2xl shadow-2xl shadow-black/50 z-50 flex flex-col overflow-hidden"
            >
                <!-- Header -->
                <div class="px-5 py-4 border-b border-[#2a2a2a] flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-sm font-semibold text-white tracking-tight">Centro de Alertas</h3>
                        <p class="text-[10px] text-[#71717a] font-medium mt-0.5">
                            {{ unreadCount }} pendiente{{ unreadCount !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-[10px] text-[#a1a1aa] hover:text-white font-bold uppercase tracking-wider transition-colors bg-[#262626] border border-[#3f3f46] px-2.5 py-1 rounded-lg"
                    >
                        Marcar todo leído
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="flex-1 overflow-y-auto overscroll-contain">
                    <template v-if="notifications.length === 0">
                        <div class="px-5 py-12 text-center">
                            <v-icon name="ri-checkbox-circle-line" scale="2" class="text-[#3f3f46] mb-3" />
                            <p class="text-xs text-[#71717a] font-medium">Todo en orden. Sin alertas pendientes.</p>
                        </div>
                    </template>

                    <template v-else>
                        <template v-for="(groupKey) in ['high', 'medium', 'info']" :key="groupKey">
                            <div v-if="groupedNotifications[groupKey].length > 0">
                                <!-- Group Header -->
                                <div class="px-5 py-2 bg-[#141414] border-b border-[#2a2a2a] sticky top-0 z-10">
                                    <div class="flex items-center gap-2">
                                        <span :class="['w-1.5 h-1.5 rounded-full', getConfig(groupKey).dot]"></span>
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-[#71717a]">
                                            {{ groupLabels[groupKey] }} ({{ groupedNotifications[groupKey].length }})
                                        </span>
                                    </div>
                                </div>

                                <!-- Notification Items -->
                                <button
                                    v-for="n in groupedNotifications[groupKey]"
                                    :key="n.id"
                                    @click="markAsRead(n)"
                                    class="w-full text-left px-5 py-3.5 border-b border-[#2a2a2a]/50 hover:bg-[#1e1e1e] transition-colors flex items-start gap-3 group"
                                    :class="n.is_read ? 'opacity-50' : ''"
                                >
                                    <!-- Urgency Dot -->
                                    <div :class="['mt-1.5 w-2 h-2 rounded-full flex-shrink-0 transition-transform group-hover:scale-125', n.is_read ? 'bg-[#3f3f46]' : getConfig(n.urgency).dot]"></div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-semibold text-white leading-tight mb-0.5" :class="n.is_read ? 'text-[#a1a1aa]' : ''">
                                            {{ n.title }}
                                        </p>
                                        <p class="text-[10px] text-[#71717a] leading-snug">
                                            {{ n.message }}
                                        </p>
                                        <p class="text-[9px] text-[#52525b] mt-1 font-medium">
                                            {{ n.created_at }}
                                        </p>
                                    </div>

                                    <!-- Arrow indicator for clickable -->
                                    <v-icon
                                        v-if="n.action_url"
                                        name="md-keyboardarrowright"
                                        scale="0.7"
                                        class="mt-1 text-[#3f3f46] group-hover:text-white transition-colors flex-shrink-0"
                                    />
                                </button>
                            </div>
                        </template>
                    </template>
                </div>

                <!-- Footer -->
                <div class="px-5 py-3 border-t border-[#2a2a2a] flex-shrink-0 bg-[#141414]">
                    <p class="text-[9px] text-[#52525b] text-center font-medium uppercase tracking-wider">
                        Últimas 50 alertas · Actualización automática
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-down-enter-active {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-down-leave-active {
    transition: all 0.15s ease-in;
}
.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
}
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.99);
}

.badge-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.badge-leave-active {
    transition: all 0.2s ease-in;
}
.badge-enter-from {
    opacity: 0;
    transform: scale(0);
}
.badge-leave-to {
    opacity: 0;
    transform: scale(0);
}
</style>
