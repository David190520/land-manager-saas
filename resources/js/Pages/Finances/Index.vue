<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    plans: Object,
    summary: Object,
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
        <div class="mb-8 animate-fade-in flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Finanzas y Recaudos</h1>
                <p class="text-xs text-[#71717a] mt-1 font-medium tracking-wide">Amortización de inmuebles comerciales</p>
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
                        <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Titular del Contrato</th>
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
                        <td colspan="9" class="text-center py-16 text-[#71717a] bg-[#121212] border-t border-[#2a2a2a]">
                            <v-icon name="md-cancel-outlined" scale="2" fill="#3f3f46" class="mx-auto block mb-3" />
                            No existen planes de pago estructurados en el momento.
                        </td>
                    </tr>
                    <tr v-for="plan in plans.data" :key="plan.id" class="border-[#2a2a2a] hover:bg-[#1e1e1e]">
                        <td class="font-semibold text-white tracking-wide py-4">{{ plan.client_name }}</td>
                        <td class="text-[#a1a1aa] font-medium">L-{{ plan.lot }}</td>
                        <td class="text-[#71717a] tracking-wide">{{ plan.project }}</td>
                        <td class="text-white font-semibold">{{ formatCurrency(plan.total_price) }}</td>
                        <td class="text-[#a1a1aa]">{{ formatCurrency(plan.down_payment) }}</td>
                        <td class="text-white border-x border-[#2a2a2a] bg-[#141414] px-4 font-bold tracking-tight">{{ formatCurrency(plan.financed_amount) }}</td>
                        <td class="min-w-[140px]">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] text-white font-bold">{{ plan.progress }}%</span>
                                <span class="text-[9px] text-[#71717a]">{{ plan.paid_installments }}/{{ plan.total_installments }}</span>
                            </div>
                            <div class="w-full h-[3px] bg-[#262626] rounded-full overflow-hidden">
                                <div class="h-full bg-white transition-all" :style="{ width: `${plan.progress}%` }"></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span :class="[
                                'inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest border',
                                plan.status === 'active' ? 'bg-white text-black border-white' :
                                plan.status === 'completed' ? 'bg-[#262626] text-[#ededed] border-[#3f3f46]' :
                                'bg-[#121212] text-[#71717a] border-[#2a2a2a]'
                            ]">
                                {{ plan.status === 'active' ? 'Vigente' : plan.status === 'completed' ? 'Saldado' : 'Fallido' }}
                            </span>
                        </td>
                        <td class="text-right pr-4">
                            <Link :href="route('finances.plan', plan.id)" class="text-[10px] font-bold uppercase tracking-widest text-[#71717a] hover:text-white transition-colors">
                                Desglosar
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
