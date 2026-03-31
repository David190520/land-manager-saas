<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const page = usePage();
const isAdmin = computed(() => ['admin', 'accountant'].includes(page.props.auth.user.role));

const props = defineProps({
    project: Object,
    blocks: Array,
});

const selectedBlock = ref(null);
const statusFilter = ref('all');
const showMapModal = ref(false);
const showEditModal = ref(false);

const form = useForm({
    name: '',
    description: '',
    location: '',
    municipality: '',
    department: '',
    total_area: '',
    price_per_m2: '',
    status: 'active',
    map_file: null,
    _method: 'PUT'
});

const openEditModal = () => {
    form.name = props.project.name || '';
    form.description = props.project.description || '';
    form.location = props.project.location || '';
    form.municipality = props.project.municipality || '';
    form.department = props.project.department || '';
    form.total_area = props.project.total_area || '';
    form.price_per_m2 = props.project.price_per_m2 || '';
    form.status = props.project.status || 'active';
    form.map_file = null;
    showEditModal.value = true;
};

const submitEdit = () => {
    form.post(route('projects.update', props.project.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
        preserveScroll: true,
    });
};

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
        'reserved': 'bg-blue-500/10 border-blue-500/20 hover:border-blue-500/40 text-blue-500/90',
        'sold': 'bg-red-500/10 border-red-500/20 hover:border-red-500/40 text-red-500/90',
        'pending_approval': 'bg-amber-500/10 border-amber-500/20 hover:border-amber-500/40 text-amber-500/90',
    }[status] || 'bg-[#18181a] border-[#2a2a2a] hover:border-[#3f3f46] text-white';
};

const getStatusLabelColor = (status) => {
    return {
        'available': 'text-white font-semibold',
        'reserved': 'text-blue-500 font-bold',
        'sold': 'text-red-500 font-bold',
        'pending_approval': 'text-amber-500 font-bold',
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
                    <div class="mt-5 flex gap-3">
                        <button v-if="project.map_file_url" @click="showMapModal = true" class="btn-primary py-2 px-4 shadow-lg flex items-center gap-2">
                            <v-icon name="md-map-outlined" scale="1" fill="black" />
                            Ver plano del proyecto
                        </button>
                        <button v-if="isAdmin" @click="openEditModal" class="btn-secondary py-2 px-4 flex items-center gap-2">
                            <v-icon name="md-edit-outlined" scale="1" />
                            Editar Proyecto
                        </button>
                    </div>
                </div>
                <!-- Stats minimalist layout -->
                <div class="grid grid-cols-5 gap-6 bg-[#141414] border border-[#2a2a2a] p-4 rounded-xl">
                    <div class="text-center">
                        <p class="text-xl font-semibold text-white">{{ project.total_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Total</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-white">{{ project.available_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Disponibles</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-amber-500">{{ project.pending_approval_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Pendientes</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-blue-500">{{ project.reserved_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Reservados</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-red-500">{{ project.sold_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-1 tracking-wider">Vendidos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 animate-slide-up" style="animation-delay: 100ms">
            <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide">
                <button
                    v-for="filter in [
                        { key: 'all', label: 'Todos', count: project.total_lots },
                        { key: 'available', label: 'Disponibles', count: project.available_lots },
                        { key: 'pending_approval', label: 'Pendientes', count: project.pending_approval_lots },
                        { key: 'reserved', label: 'Reservados', count: project.reserved_lots },
                        { key: 'sold', label: 'Vendidos', count: project.sold_lots },
                    ]"
                    :key="filter.key"
                    @click="statusFilter = filter.key"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border whitespace-nowrap',
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
                        <span>{{ block.total_lots }} L</span>
                        <span>•</span>
                        <span class="text-white">{{ block.available_lots }} D</span>
                        <span class="text-amber-500">{{ block.pending_approval_lots }} P</span>
                        <span class="text-blue-500">{{ block.reserved_lots }} R</span>
                        <span class="text-red-500">{{ block.sold_lots }} V</span>
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
                                <div v-if="lot.status === 'sold'" class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
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

        <!-- Map File Modal -->
        <Modal :show="showMapModal" maxWidth="5xl" @close="showMapModal = false">
            <div class="p-6 relative bg-[#18181a] rounded-2xl border border-[#2a2a2a]">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-[#2a2a2a]">
                    <h2 class="text-lg font-semibold text-white tracking-tight flex items-center gap-2">
                        <v-icon name="md-map-outlined" scale="1.2" /> Plano de {{ project.name }}
                    </h2>
                    <button @click="showMapModal = false" class="text-[#71717a] hover:text-white transition-colors">
                        <v-icon name="md-close" scale="1.2" />
                    </button>
                </div>
                
                <div class="w-full h-[70vh] rounded-lg overflow-hidden bg-[#121212] border border-[#2a2a2a] flex items-center justify-center">
                    <iframe v-if="project.map_file_url && project.map_file_url.endsWith('.pdf')" :src="project.map_file_url" class="w-full h-full border-0"></iframe>
                    <img v-else-if="project.map_file_url" :src="project.map_file_url" class="w-full h-full object-contain" alt="Plano del proyecto" />
                    <div v-else class="text-center text-[#71717a]">
                        <v-icon name="md-brokenimage-outlined" scale="2" class="mb-2" />
                        <p>Plano no disponible</p>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Edit Project Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#000000] opacity-80 backdrop-blur-sm" @click="showEditModal = false"></div>
                    <div class="relative w-full max-w-lg bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-8 animate-slide-up shadow-2xl">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-white tracking-tight">Editar Proyecto</h2>
                            <button @click="showEditModal = false" class="text-[#71717a] hover:text-white">
                                <v-icon name="md-close" />
                            </button>
                        </div>
                        <form @submit.prevent="submitEdit" class="space-y-4 max-h-[70vh] overflow-y-auto pr-2 custom-scrollbar">
                            <div>
                                <label class="label-dark">Nombre del Proyecto</label>
                                <input v-model="form.name" type="text" class="input-dark bg-[#141414]" placeholder="Ej: Proyecto Alfa" />
                                <p v-if="form.errors.name" class="text-[#ef4444] text-[10px] mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="label-dark">Descripción</label>
                                <textarea v-model="form.description" class="input-dark bg-[#141414]" rows="2" placeholder="Detalles generales..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-dark">Ubicación</label>
                                    <input v-model="form.location" type="text" class="input-dark bg-[#141414]" placeholder="Sector" />
                                    <p v-if="form.errors.location" class="text-[#ef4444] text-[10px] mt-1">{{ form.errors.location }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Municipio</label>
                                    <input v-model="form.municipality" type="text" class="input-dark bg-[#141414]" placeholder="Ciudad" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-dark">M² Totales</label>
                                    <input v-model="form.total_area" type="number" class="input-dark bg-[#141414]" placeholder="0" />
                                </div>
                                <div>
                                    <label class="label-dark">Precio base m²</label>
                                    <input v-model="form.price_per_m2" type="number" class="input-dark bg-[#141414]" placeholder="0" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="label-dark">Estado del Proyecto</label>
                                    <select v-model="form.status" class="input-dark bg-[#141414]">
                                        <option value="active">Activo</option>
                                        <option value="paused">Pausado</option>
                                        <option value="completed">Completado</option>
                                    </select>
                                    <p v-if="form.errors.status" class="text-[#ef4444] text-[10px] mt-1">{{ form.errors.status }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="label-dark flex items-center gap-1">
                                    <v-icon name="md-map-outlined" scale="0.8" /> Plano / Mapa del Proyecto
                                </label>
                                <input type="file" @input="form.map_file = $event.target.files[0]" accept=".pdf,.png,.jpg,.jpeg" class="input-dark bg-[#141414] file:mr-4 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-[#262626] file:text-white hover:file:bg-[#3f3f46] text-xs transition-colors" />
                                <p v-if="form.errors.map_file" class="text-[#ef4444] text-[10px] mt-1">{{ form.errors.map_file }}</p>
                                <p class="text-[9px] text-[#71717a] mt-1">Soporta PDF, PNG, JPG hasta 10MB. Al subir uno nuevo reemplazará al anterior.</p>
                            </div>
                            <div class="flex justify-end gap-3 pt-6 border-t border-[#2a2a2a] mt-6">
                                <button type="button" @click="showEditModal = false" class="btn-secondary">Cancelar</button>
                                <button type="submit" class="btn-primary" :disabled="form.processing">
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
