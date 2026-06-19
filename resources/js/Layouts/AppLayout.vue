<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import NotificationCenter from '@/Components/NotificationCenter.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const showMobileMenu = ref(false);

const navigation = [
    {
        name: 'Dashboard',
        href: 'dashboard',
        icon: 'md-spacedashboard-outlined',
        roles: ['admin', 'accountant', 'secretary'],
    },
    {
        name: 'Proyectos',
        href: 'projects.index',
        icon: 'md-workoutline',
        roles: ['admin', 'accountant', 'secretary'],
    },
    {
        name: 'Clientes',
        href: 'clients.index',
        icon: 'md-peopleoutline',
        roles: ['admin', 'accountant', 'secretary'],
    },
    {
        name: 'Finanzas',
        href: 'finances.index',
        icon: 'md-attachmoney-outlined',
        roles: ['admin', 'accountant'],
    },
    {
        name: 'Configuración',
        href: 'settings.index',
        icon: 'md-settings-outlined',
        roles: ['admin'],
    },
];

const filteredNav = computed(() =>
    navigation.filter(item => item.roles.includes(user.value.role))
);

const isActive = (routeName) => {
    return route().current(routeName) || route().current(routeName + '.*');
};

const roleLabel = computed(() => {
    const labels = {
        admin:      'Administrador',
        accountant: 'Contador',
        secretary:  'Secretaria',
    };
    return labels[user.value.role] || user.value.role;
});
</script>

<template>
    <div class="min-h-screen bg-[#121212] flex">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-[#18181a] border-r border-[#2a2a2a] fixed inset-y-0 z-30">
            <!-- Logo Area -->
            <div class="px-6 py-6 border-b border-[#2a2a2a]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#262626] flex items-center justify-center">
                        <v-icon name="ri-landscape-line" scale="1.2" fill="#ededed" />
                    </div>
                    <div>
                        <h1 class="text-sm font-semibold text-white tracking-tight">Land Manager</h1>
                        <p class="text-xs text-[#a1a1aa]">Gestión Inmobiliaria</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-8 space-y-1 overflow-y-auto">
                <p class="px-4 text-[10px] font-semibold text-[#71717a] uppercase tracking-wider mb-4">Menú</p>
                <Link
                    v-for="item in filteredNav"
                    :key="item.href"
                    :href="route(item.href)"
                    :class="[
                        'sidebar-link',
                        isActive(item.href) ? 'sidebar-link-active' : 'sidebar-link-inactive'
                    ]"
                >
                    <v-icon :name="item.icon" scale="1.1" :fill="isActive(item.href) ? '#ffffff' : '#a1a1aa'"/>
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- User Card -->
            <div class="p-4 border-t border-[#2a2a2a]">
                <div class="p-3 bg-[#1e1e1e] rounded-xl border border-[#2a2a2a]">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#262626] flex items-center justify-center text-white font-medium text-sm">
                            {{ user.name?.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                            <p class="text-[10px] text-[#a1a1aa]">{{ roleLabel }}</p>
                        </div>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="mt-3 w-full text-xs text-[#a1a1aa] hover:text-white bg-[#262626] border border-[#3f3f46] hover:bg-[#3f3f46] transition-colors flex items-center justify-center gap-2 py-2 rounded-lg"
                    >
                        <v-icon name="md-logout" scale="0.8" />
                        Cerrar Sesión
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 min-h-screen relative">
            <!-- Top Bar -->
            <div class="sticky top-0 z-20 bg-[#121212]/80 backdrop-blur-xl border-b border-[#2a2a2a]/50 px-6 lg:px-10 py-3 flex items-center justify-end">
                <NotificationCenter />
            </div>
            <Toast />
            <div class="p-6 lg:p-10">
                <slot />
            </div>
        </main>
    </div>
</template>
