<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    plans: Object,
    summary: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const handleSearch = debounce(() => {
    router.get(route('finances.index'), { 
        search: search.value, 
        status: status.value 
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    });
}, 300);

watch([search, status], () => {
    handleSearch();
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <AppLayout>
        <Head title="Contabilidad" />

        <!-- Header -->
        <div class="mb-8 animate-fade-in flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Finanzas y Recaudos</h1>
                <p class="text-xs text-[#71717a] mt-1 font-medium tracking-wide">Amortización de inmuebles comerciales</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative min-w-[280px]">
                    <v-icon name="md-search" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#71717a]" scale="0.9" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Buscar por nombre, identificación o lote..." 
                        class="bg-[#18181a] border border-[#2a2a2a] text-white text-xs rounded-xl pl-9 pr-4 py-2.5 w-full focus:ring-0 focus:border-[#3f3f46] transition-all"
                    />
                </div>
                <select 
                    v-model="status" 
                    class="bg-[#18181a] border border-[#2a2a2a] text-white text-xs rounded-xl px-4 py-2.5 focus:ring-0 focus:border-[#3f3f46] transition-all min-w-[150px]"
                >
                    <option value="">Todos los Estados</option>
                    <option value="overdue">En Mora</option>
                    <option value="pending_approval">Pdte. Aprobación</option>
                    <option value="pending_initial_payment">Cuota Inicial Pdte.</option>
                    <option value="active">Vigentes</option>
                    <option value="completed">Saldados</option>
                    <option value="cancelled">Cancelados</option>
                </select>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-[#18181a] border border-[#2a2a2a] p-6 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors">
                <div class="flex items-center gap-3 mb-4 border-b border-[#2a2a2a] pb-3">
                    <div class="w-8 h-8 rounded-lg bg-[#262626] border border-[#3f3f46] flex items-center justify-center">
                        <v-icon name="md-attachmoney-outlined" scale="1.1" fill="#ededed" />
                    </div>
                    <p class="text-[10px] text-[#71717a] font-bold uppercase tracking-widest leading-none">Total<br>Financiado</p>
                </div>
                <p class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(summary.totalFinanced) }}</p>
            </div>
            
            <div class="bg-[#18181a] border border-[#2a2a2a] p-6 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors" style="animation-delay: 100ms">
                <div class="flex items-center gap-3 mb-4 border-b border-[#2a2a2a] pb-3">
                    <div class="w-8 h-8 rounded-lg bg-[#262626] border border-[#3f3f46] flex items-center justify-center">
                        <v-icon name="ri-checkbox-circle-line" scale="1.1" fill="#ededed" />
                    </div>
                    <p class="text-[10px] text-[#71717a] font-bold uppercase tracking-widest leading-none">Tesorería<br>(Recaudos)</p>
                </div>
                <p class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(summary.totalCollected) }}</p>
            </div>
            
            <div class="bg-[#18181a] border border-[#2a2a2a] p-6 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors" style="animation-delay: 200ms">
                <div class="flex items-center gap-3 mb-4 border-b border-[#2a2a2a] pb-3">
                    <div class="w-8 h-8 rounded-lg bg-[#262626] border border-[#3f3f46] flex items-center justify-center">
                        <v-icon name="md-warningamber-outlined" scale="1.1" fill="#ededed" />
                    </div>
                    <p class="text-[10px] text-[#71717a] font-bold uppercase tracking-widest leading-none">Cartera<br>Activa</p>
                </div>
                <p class="text-2xl font-bold text-white tracking-tight">{{ summary.pendingPayments }} <span class="text-[10px] tracking-normal uppercase font-semibold text-[#71717a]">Pagos Pendientes</span></p>
            </div>
        </div>

        <!-- Payment Plans Table -->
        <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden animate-slide-up" style="animation-delay: 300ms">
            <div class="px-6 py-4 border-b border-[#2a2a2a] flex items-center gap-3 bg-[#121212]">
                <v-icon name="md-spacedashboard-outlined" scale="1.2" fill="#71717a"/>
                <h3 class="text-xs font-bold text-white tracking-widest uppercase">Libro Mayor de Cobros</h3>
            </div>
            <table class="table-dark">
                <thead>
                    <tr>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Fecha Contrato</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Titular del Contrato</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Celular</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Activo Fijo</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Desarrollo</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Venta Total</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Anticipo</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Sujeto a Cobro</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Auditoría</th>
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a] text-center">Término</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    <tr v-if="plans.data.length === 0">
                        <td colspan="11" class="text-center py-16 text-[#71717a] bg-[#121212] border-t border-[#2a2a2a]">
                            <v-icon name="md-cancel-outlined" scale="2" fill="#3f3f46" class="mx-auto block mb-3" />
                            No existen planes de pago estructurados en el momento.
                        </td>
                    </tr>
                    <tr v-for="plan in plans.data" :key="plan.id" 
                        class="border-[#2a2a2a] transition-colors"
                        :class="[
                            plan.is_overdue ? 'bg-[#2a1313] hover:bg-[#3d1a1a]' :
                            plan.status === 'pending_initial_payment' ? 'bg-amber-900/10 hover:bg-amber-900/20' :
                            plan.status === 'completed' ? 'bg-[#132a13] hover:bg-[#1a3d1a]' :
                            plan.status === 'cancelled' ? 'bg-[#1a1a1a] opacity-60 hover:opacity-100 hover:bg-[#222222]' :
                            'hover:bg-[#1e1e1e]'
                        ]"
                    >
                        <td class="text-[#71717a] font-medium py-4">{{ plan.created_at }}</td>
                        <td class="font-semibold text-white tracking-wide">{{ plan.client_name }}</td>
                        <td class="text-[#a1a1aa] font-medium">{{ plan.client_phone || '—' }}</td>
                        <td class="text-[#a1a1aa] font-medium">L-{{ plan.lot }}</td>
                        <td class="text-[#71717a] tracking-wide">{{ plan.project }}</td>
                        <td class="text-white font-semibold">{{ formatCurrency(plan.total_price) }}</td>
                        <td class="text-[#a1a1aa]">{{ formatCurrency(plan.down_payment) }}</td>
                        <td class="text-white border-x border-[#2a2a2a] bg-[#141414] px-4 font-bold tracking-tight">{{ formatCurrency(plan.financed_amount) }}</td>
                        <td class="min-w-[140px]">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-white font-bold">{{ plan.progress }}%</span>
                                    <span class="text-[9px] text-[#71717a]">{{ plan.paid_installments }}/{{ plan.total_installments }}</span>
                                </div>
                                <span v-if="plan.is_overdue" class="text-[8px] font-black bg-red-500 text-white px-1 rounded animate-pulse uppercase tracking-tighter">
                                    En Mora
                                </span>
                            </div>
                            <div class="w-full h-[3px] bg-[#262626] rounded-full overflow-hidden">
                                <div class="h-full bg-white transition-all" :style="{ width: `${plan.progress}%`, backgroundColor: plan.status === 'completed' ? '#4ade80' : 'white' }"></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span :class="[
                                'inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest border',
                                plan.reservation_status === 'pending_approval' ? 'bg-amber-500/20 text-amber-500 border-amber-500/30' :
                                plan.status === 'pending_initial_payment' ? 'bg-orange-500/20 text-orange-400 border-orange-500/30' :
                                plan.status === 'active' ? 'bg-white text-black border-white' :
                                plan.status === 'completed' ? 'bg-[#132a13] text-[#4ade80] border-[#4ade80]/30' :
                                'bg-[#121212] text-[#71717a] border-[#2a2a2a]'
                            ]">
                                {{
                                    plan.reservation_status === 'pending_approval' ? 'Pdte. Aprob.' :
                                    plan.status === 'pending_initial_payment' ? 'CI Pendiente' :
                                    plan.status === 'active' ? 'Vigente' :
                                    plan.status === 'completed' ? 'Saldado' : 'Cancelado'
                                }}
                            </span>
                        </td>
                        <td class="text-right pr-4">
                            <Link :href="route('finances.plan', plan.id)" class="text-[10px] font-bold uppercase tracking-widest text-[#71717a] hover:text-white transition-colors">
                                Ver detalle
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="plans.links && plans.links.length > 3" class="px-6 py-4 border-t border-[#2a2a2a] flex items-center justify-between bg-[#121212]">
                <p class="text-[10px] font-semibold tracking-widest text-[#71717a] uppercase">
                     Mostrando folio {{ plans.current_page }} de {{ plans.last_page }}
                </p>
                <div class="flex gap-1.5">
                    <Link
                        v-for="link in plans.links"
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
