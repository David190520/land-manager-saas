<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CreateClientModal from './Partials/CreateClientModal.vue';

const props = defineProps({
    clients: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showingCreateModal = ref(false);
let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('clients.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const openCreateModal = () => {
    showingCreateModal.value = true;
};
</script>

<template>
    <AppLayout>
        <Head title="Clientes" />

        <!-- Header -->
        <div class="flex items-center justify-between mb-8 animate-fade-in">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Directorio de Clientes</h1>
                <p class="text-xs text-[#71717a] mt-1 font-medium tracking-wide">Registro oficial</p>
            </div>
            <button @click="openCreateModal" class="btn-primary">
                <v-icon name="md-add" fill="black" class="mr-2" />
                Registrar Ficha
            </button>
        </div>

        <CreateClientModal :show="showingCreateModal" @close="showingCreateModal = false" />

        <!-- Search -->
        <div class="mb-6 animate-slide-up">
            <div class="relative max-w-sm">
                <v-icon name="md-workoutline" class="absolute left-4 top-1/2 -translate-y-1/2 text-[#71717a]" scale="0.9" />
                <input
                    v-model="search"
                    type="text"
                    class="input-dark pl-12 bg-[#18181a] border-[#2a2a2a] text-sm h-11 transition-colors hover:border-[#3f3f46] focus:border-white focus:ring-0"
                    placeholder="Filtrar número de documento o nombre..."
                />
            </div>
        </div>

        <!-- Table -->
        <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden animate-slide-up" style="animation-delay: 100ms">
            <table class="table-dark">
                <thead>
                    <tr>
                        <th class="font-bold text-[10px] tracking-widest text-[#71717a]">Titular</th>
                        <th class="font-bold text-[10px] tracking-widest text-[#71717a]">Documento</th>
                        <th class="font-bold text-[10px] tracking-widest text-[#71717a]">Contacto</th>
                        <th class="font-bold text-[10px] tracking-widest text-[#71717a]">Email</th>
                        <th class="font-bold text-[10px] tracking-widest text-[#71717a]">Ciudad</th>
                        <th class="font-bold text-[10px] tracking-widest text-[#71717a] text-center">Bienes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    <tr v-if="clients.data.length === 0">
                        <td colspan="7" class="text-center py-16 text-[#71717a] border-t border-[#2a2a2a]">
                            <v-icon name="md-peopleoutline" scale="2" fill="#3f3f46" class="mx-auto block mb-3" />
                            {{ search ? 'Búsqueda sin resultados en el padrón' : 'El padrón de clientes está vacío' }}
                        </td>
                    </tr>
                    <tr v-for="client in clients.data" :key="client.id" class="border-t border-[#2a2a2a] hover:bg-[#1e1e1e] transition-colors">
                        <td class="py-4">
                            <Link :href="route('clients.show', client.id)" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                                <div class="w-8 h-8 rounded-full bg-[#262626] border border-[#3f3f46] flex items-center justify-center text-white text-[10px] font-bold">
                                    {{ client.first_name.charAt(0) }}{{ client.last_name.charAt(0) }}
                                </div>
                                <span class="font-semibold text-white tracking-wide">{{ client.full_name }}</span>
                            </Link>
                        </td>
                        <td class="text-[#a1a1aa] font-medium tracking-wide">
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-[#262626] text-[#71717a] border border-[#3f3f46] mr-2">{{ client.document_type }}</span>
                            {{ client.document_number }}
                        </td>
                        <td class="text-[#a1a1aa]">{{ client.phone }}</td>
                        <td class="text-[#71717a]">{{ client.email || 'N/D' }}</td>
                        <td class="text-[#71717a] uppercase tracking-wider text-[10px]">{{ client.city || 'N/D' }}</td>
                        <td class="text-center">
                            <span class="px-2 py-0.5 rounded border border-[#3f3f46] text-[10px] font-bold bg-[#262626] text-white">
                                {{ client.reservations_count }}
                            </span>
                        </td>
                        <td class="text-right pr-4">
                            <Link :href="route('clients.show', client.id)" class="text-[10px] font-bold uppercase tracking-widest text-[#71717a] hover:text-white transition-colors">
                                Abrir
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="clients.links && clients.links.length > 3" class="px-6 py-4 border-t border-[#2a2a2a] flex items-center justify-between bg-[#121212]">
                <p class="text-[10px] font-semibold tracking-widest text-[#71717a] uppercase">
                    Página {{ clients.current_page }} de {{ clients.last_page }}
                </p>
                <div class="flex gap-1.5">
                    <Link
                        v-for="link in clients.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'w-7 h-7 flex items-center justify-center rounded text-[10px] font-bold transition-all border',
                            link.active ? 'bg-white text-black border-white' : 'bg-[#18181a] border-[#3f3f46] text-[#71717a] hover:text-white hover:border-[#a1a1aa]',
                            !link.url ? 'opacity-30 cursor-not-allowed border-[#2a2a2a]' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
