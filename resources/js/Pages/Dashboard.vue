<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentReservations: Array,
    upcomingPayments: Array,
    projects: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const metrics = [
    { label: 'Total Lotes', key: 'totalLots', icon: 'ri-landscape-line' },
    { label: 'Disponibles', key: 'availableLots', icon: 'ri-checkbox-circle-line' },
    { label: 'Reservados', key: 'md-timelabor-outlined' },
    { label: 'Vendidos', key: 'soldLots', icon: 'ri-coin-line' },
    { label: 'Clientes', key: 'totalClients', icon: 'md-peopleoutline' },
    { label: 'Proyectos', key: 'totalProjects', icon: 'md-workoutline' },
];
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <!-- Header -->
        <div class="mb-8 animate-fade-in flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Dashboard</h1>
                <p class="text-sm text-[#71717a] mt-1">Gestión general del portafolio inmobiliario</p>
            </div>
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
            <div
                v-for="(metric, index) in metrics"
                :key="metric.key"
                class="bg-[#18181a] border border-[#2a2a2a] p-5 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors"
                :style="{ animationDelay: `${index * 50}ms` }"
            >
                <div class="flex flex-col gap-3">
                    <div class="w-8 h-8 rounded-full bg-[#1e1e1e] flex items-center justify-center border border-[#3f3f46]">
                        <v-icon :name="metric.icon || 'ri-information-line'" scale="0.9" fill="#ededed" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white tracking-tight">{{ stats[metric.key] }}</p>
                        <p class="text-[10px] text-[#71717a] uppercase mt-1">{{ metric.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-6">
            <!-- Projects Overview -->
            <div class="animate-slide-up" style="animation-delay: 150ms">
                <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-sm font-semibold text-white tracking-wide">Proyectos Activos</h2>
                        <Link :href="route('projects.index')" class="text-xs text-[#a1a1aa] bg-[#1e1e1e] px-3 py-1.5 rounded-lg border border-[#3f3f46] hover:text-white hover:bg-[#262626]">
                            Ver Todos
                        </Link>
                    </div>
                    
                    <div class="space-y-4">
                        <Link
                            v-for="project in projects"
                            :key="project.id"
                            :href="route('projects.show', project.id)"
                            class="block p-4 rounded-xl border border-[#2a2a2a] bg-[#141414] hover:bg-[#1e1e1e] hover:border-[#3f3f46] transition-all"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-[#262626] flex items-center justify-center border border-[#3f3f46]">
                                        <v-icon name="md-workoutline" scale="1.1" fill="#a1a1aa" />
                                    </div>
                                    <div>
                                        <h3 class="text-white text-sm font-medium">{{ project.name }}</h3>
                                        <p class="text-[10px] text-[#71717a] mt-0.5">{{ project.location }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-white">{{ project.total_lots }} lotes</div>
                                    <div class="text-[10px] text-[#71717a] mt-0.5">{{ project.available_lots }} disp.</div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Side Widgets -->
            <div class="space-y-6">
                <!-- Revenue Summary -->
                <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-6 animate-slide-up" style="animation-delay: 200ms">
                    <h3 class="text-xs font-semibold text-[#71717a] uppercase mb-1">Total Ingresos</h3>
                    <p class="text-3xl font-bold text-white mt-2 tracking-tight">{{ formatCurrency(stats.totalRevenue) }}</p>
                    <div class="flex items-center gap-2 mt-4 text-[10px] bg-[#141414] p-3 rounded-xl border border-[#2a2a2a]">
                        <span class="text-white bg-[#262626] px-2 py-0.5 rounded">{{ stats.soldLots }} lotes vendidos</span>
                        <span class="text-[#71717a]">de {{ stats.totalLots }}</span>
                    </div>
                </div>

                <!-- Recent Reservations -->
                <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-6 animate-slide-up" style="animation-delay: 300ms">
                    <h3 class="text-sm font-semibold text-white tracking-wide mb-4">Reservas Nuevas</h3>
                    <div v-if="recentReservations.length === 0" class="text-center py-6 text-xs text-[#71717a]">
                        No hay reservas recientes
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="r in recentReservations" :key="r.id"
                             class="flex items-center justify-between bg-[#141414] border border-[#2a2a2a] p-3 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#1e1e1e] flex items-center justify-center border border-[#3f3f46]">
                                    <v-icon name="md-peopleoutline" scale="0.8" fill="#a1a1aa" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-white">{{ r.client_name }}</p>
                                    <p class="text-[10px] text-[#71717a]">{{ r.lot }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded bg-[#262626] border border-[#3f3f46] text-[10px] text-[#ededed]">
                                {{ r.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
