<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    project: Object,
    blocks: Array,
});

const selectedBlock = ref(null);
const statusFilter = ref('all');

const filteredBlocks = computed(() => {
    if (!selectedBlock.value) return props.blocks;
    return props.blocks.filter(b => b.id === selectedBlock.value);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const getStatusClasses = (status) => {
    return {
        'available': 'bg-[#18181a] border-[#2a2a2a] hover:border-white/20 text-white',
        'reserved': 'bg-[#121212] border-[#3f3f46] hover:border-white/40 text-[#a1a1aa]',
        'sold': 'bg-[#0a0a0a] border-[#1e1e1e] hover:border-[#2a2a2a] text-[#71717a] opacity-50',
    }[status] || 'bg-[#18181a] border-[#2a2a2a] hover:border-[#3f3f46] text-white';
};

const getStatusLabelColor = (status) => {
    return {
        'available': 'text-white font-semibold',
        'reserved': 'text-[#a1a1aa]',
        'sold': 'text-[#71717a]',
    }[status] || 'text-[#71717a]';
};

const getFilteredLots = (lots) => {
    if (statusFilter.value === 'all') return lots;
    return lots.filter(l => l.status === statusFilter.value);
};
</script>

<template>
    <AppLayout>
        <Head :title="project.name" />

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-[#71717a] mb-6 animate-fade-in font-medium">
            <Link :href="route('projects.index')" class="hover:text-white transition-colors">Proyectos</Link>
            <v-icon name="md-keyboardarrowright" scale="0.8" />
            <span class="text-[#ededed]">{{ project.name }}</span>
        </div>

        <!-- Project Header -->
        <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl mb-8 animate-slide-up">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-2xl font-semibold text-white tracking-tight">{{ project.name }}</h1>
                    <p class="text-[12px] text-[#a1a1aa] mt-2 flex items-center gap-1">
                        <v-icon name="md-locationon-outlined" scale="0.8" />
                        {{ project.location }} • {{ project.municipality }}
                    </p>
                    <p v-if="project.description" class="text-xs text-[#71717a] mt-4 max-w-2xl leading-relaxed">{{ project.description }}</p>
                </div>
                <!-- Stats minimalist layout -->
                <div class="grid grid-cols-4 gap-6 bg-[#141414] border border-[#2a2a2a] p-4 rounded-xl">
                    <div class="text-center">
                        <p class="text-xl font-semibold text-white">{{ project.total_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Total</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-white">{{ project.available_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Disp.</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-[#a1a1aa]">{{ project.reserved_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Resrv.</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-[#71717a]">{{ project.sold_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Vend.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 animate-slide-up" style="animation-delay: 100ms">
            <div class="flex items-center gap-2">
                <button
                    v-for="filter in [
                        { key: 'all', label: 'Todos', count: project.total_lots },
                        { key: 'available', label: 'Disp.', count: project.available_lots },
                        { key: 'reserved', label: 'Resrv.', count: project.reserved_lots },
                        { key: 'sold', label: 'Vend.', count: project.sold_lots },
                    ]"
                    :key="filter.key"
                    @click="statusFilter = filter.key"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border',
                        statusFilter === filter.key
                            ? 'bg-white text-black border-white'
                            : 'bg-[#18181a] border-[#2a2a2a] text-[#a1a1aa] hover:text-white hover:border-[#3f3f46]'
                    ]"
                >
                    {{ filter.label }} ({{ filter.count }})
                </button>
            </div>
            <div>
                <select v-model="selectedBlock" class="input-dark bg-[#18181a] text-xs h-9 py-0 w-48 border-[#2a2a2a]">
                    <option :value="null">Todas las Manzanas</option>
                    <option v-for="block in blocks" :key="block.id" :value="block.id">
                        {{ block.name }} ({{ block.total_lots }} lotes)
                    </option>
                </select>
            </div>
        </div>

        <!-- Blocks Grid -->
        <div class="space-y-10">
            <div
                v-for="(block, bIndex) in filteredBlocks"
                :key="block.id"
                class="animate-slide-up"
                :style="{ animationDelay: `${bIndex * 80 + 200}ms` }"
            >
                <div class="flex items-center gap-4 mb-4 pb-2 border-b border-[#2a2a2a]">
                    <h3 class="text-sm font-semibold text-white tracking-widest">{{ block.name }}</h3>
                    <div class="flex items-center gap-3 text-[10px] uppercase font-semibold text-[#71717a] tracking-wider">
                        <span>{{ block.total_lots }} LOT.</span>
                        <span>•</span>
                        <span class="text-white">{{ block.available_lots }} DISP</span>
                        <span class="text-[#a1a1aa]">{{ block.reserved_lots }} RES.</span>
                        <span class="text-[#71717a]">{{ block.sold_lots }} VND.</span>
                    </div>
                </div>

                <!-- Lot Cards Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-6 xl:grid-cols-8 gap-2.5">
                    <Link
                        v-for="lot in getFilteredLots(block.lots)"
                        :key="lot.id"
                        :href="route('lots.show', lot.id)"
                        :class="[
                            'relative p-3 rounded-xl border transition-all duration-300 cursor-pointer',
                            getStatusClasses(lot.status)
                        ]"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold">L-{{ lot.lot_number }}</span>
                            <div class="flex gap-0.5">
                                <div v-if="lot.status === 'sold'" class="w-1.5 h-1.5 rounded-full bg-[#3f3f46]"></div>
                                <div v-if="lot.status === 'reserved'" class="w-1.5 h-1.5 rounded-full bg-[#71717a]"></div>
                            </div>
                        </div>
                        <p class="text-xs font-semibold mb-1">{{ lot.area }}<span class="text-[8px] opacity-70 ml-0.5">M²</span></p>
                        <p class="text-[9px] opacity-60 font-medium tracking-wide mb-2">{{ formatCurrency(lot.price) }}</p>
                        <p :class="[
                            'text-[9px] uppercase tracking-wider',
                            getStatusLabelColor(lot.status)
                        ]">
                            {{ lot.status_label }}
                        </p>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
