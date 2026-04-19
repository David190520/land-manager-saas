<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    plan: Object,
    client: Object,
    lot: Object,
    project: Object,
    amortizationTable: Array,
});

const page = usePage();
const isAdmin = computed(() => ['admin', 'accountant'].includes(page.props.auth.user.role));

const payingPayment = ref(null);
const showCancelModal = ref(false);
const cancelForm = useForm({});

const cancelPlan = () => {
    cancelForm.post(route('finances.plan.cancel', props.plan.id), {
        onSuccess: () => {
            showCancelModal.value = false;
        }
    });
};

const paymentForm = useForm({
    payment_method: 'cash',
    reference_number: '',
    notes: '',
});

const openPaymentModal = (payment) => {
    payingPayment.value = payment;
    paymentForm.reset();
};

const submitPayment = () => {
    paymentForm.post(route('finances.payment.record', payingPayment.value.payment_id), {
        onSuccess: () => {
            payingPayment.value = null;
            paymentForm.reset();
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const getStatusClasses = (row, isInitial = false) => {
    if (row.is_overdue) {
        return isInitial 
            ? 'bg-amber-500/20 text-amber-500 border-amber-500/30 font-black animate-pulse'
            : 'bg-rose-500/20 text-rose-500 border-rose-500/30 font-black animate-pulse';
    }
    return {
        'pending': 'bg-[#18181a] text-[#ededed] border-[#3f3f46]',
        'paid': 'bg-white text-black border-white',
    }[row.status] || 'bg-[#18181a] text-[#a1a1aa] border-[#2a2a2a]';
};
</script>

<template>
    <AppLayout>
        <Head title="Contabilidad: Amortización" />

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-[#71717a] mb-8 animate-fade-in font-medium">
            <Link :href="route('finances.index')" class="hover:text-white transition-colors">Libro Mayor</Link>
            <v-icon name="md-keyboardarrowright" scale="0.8" />
            <span class="text-[#ededed]">Expediente P-{{ plan.id }}</span>
        </div>

        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Registro de Amortizaciones</h1>
                <p class="text-xs text-[#71717a] mt-1 font-medium tracking-wide">
                    Operación libre de intereses. Cuotas fijas mensuales.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button v-if="isAdmin && plan.status === 'active'" @click="showCancelModal = true" class="bg-rose-500/10 text-rose-500 border border-rose-500/30 hover:bg-rose-500 hover:text-white px-3 py-1.5 rounded text-[10px] font-bold uppercase tracking-widest transition-colors flex items-center gap-1">
                    <v-icon name="md-cancel-outlined" scale="0.8" />
                    Invalidar Contrato
                </button>
                <div class="bg-[#121212] border border-[#2a2a2a] rounded-lg px-4 py-2 flex items-center gap-4 text-xs font-semibold uppercase tracking-widest text-[#71717a]">
                    <span>Status de Plan:</span>
                    <span :class="[
                        'px-2 py-0.5 rounded border',
                        plan.reservation_status === 'pending_approval' ? 'bg-amber-500/20 text-amber-500 border-amber-500/30' :
                        plan.status === 'active' ? 'bg-white text-black border-white' :
                        plan.status === 'completed' ? 'bg-[#262626] text-[#ededed] border-[#4b5563]' :
                        'bg-[#121212] text-[#71717a] border-[#2a2a2a]'
                    ]">
                        {{ plan.reservation_status === 'pending_approval' ? 'Pdte. Aprobación' : plan.status === 'active' ? 'En Curso' : plan.status === 'completed' ? 'Ejecutado' : 'Invalidado' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Header Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-6 mb-8">
            <div class="bg-[#18181a] border border-[#2a2a2a] p-6 rounded-2xl animate-slide-up flex flex-col justify-between">
                <div>
                    <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 border-b border-[#2a2a2a] pb-3">Sujeto a Cobro</h3>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-[#262626] border border-[#3f3f46] flex items-center justify-center text-white text-lg font-bold">
                            {{ client.full_name.charAt(0) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white tracking-wide">{{ client.full_name }}</p>
                            <p class="text-[10px] text-[#71717a] uppercase tracking-wider mt-1 font-medium">NIT/CC: {{ client.document_number }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-[#141414] border border-[#2a2a2a] rounded-xl p-4">
                    <p class="text-[10px] text-[#71717a] uppercase font-bold tracking-widest mb-2 border-b border-[#2a2a2a] pb-2">Garantía Inmueble</p>
                    <p class="text-sm font-bold text-white uppercase tracking-wider">{{ lot.full_identifier }}</p>
                    <p class="text-xs text-[#a1a1aa] mt-1">{{ project.name }} / {{ lot.area }} m²</p>
                </div>
            </div>

            <!-- Balances -->
            <div class="bg-[#18181a] border border-[#2a2a2a] p-6 rounded-2xl animate-slide-up" style="animation-delay: 100ms">
                 <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 border-b border-[#2a2a2a] pb-3 flex items-center gap-2">
                    <v-icon name="md-spacedashboard-outlined" scale="1.1" /> Cuadro Resumen Patrimonial
                 </h3>

                 <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8 border border-[#2a2a2a] p-4 rounded-xl bg-[#121212]">
                     <div>
                         <p class="text-[9px] text-[#71717a] uppercase tracking-wider font-bold mb-1">Monto Nominal</p>
                         <p class="text-[15px] font-bold text-white">{{ formatCurrency(plan.total_price) }}</p>
                     </div>
                     <div class="border-l border-[#2a2a2a] pl-4">
                         <p class="text-[9px] text-[#71717a] uppercase tracking-wider font-bold mb-1">Fondo de Enganche</p>
                         <p class="text-[15px] font-bold text-[#a1a1aa]">{{ formatCurrency(plan.down_payment) }}</p>
                     </div>
                     <div class="border-l border-[#2a2a2a] pl-4 bg-[#1e1e1e] -m-4 p-4 rounded-r-none md:rounded-l-none text-right md:text-left shadow-inner text-white border-b md:border-b-0">
                          <p class="text-[9px] uppercase tracking-wider font-bold mb-1 opacity-70">Capital Directo Prestado</p>
                          <p class="text-[15px] font-bold tracking-tight">{{ formatCurrency(plan.financed_amount) }}</p>
                     </div>
                     <div class="border-l lg:border-l-0 xl:border-l border-[#2a2a2a] pl-4 bg-[#1e1e1e] -m-4 p-4 shadow-inner text-white rounded-r-xl">
                          <p class="text-[9px] uppercase tracking-wider font-bold mb-1 opacity-70">Factor Obligación/Mes</p>
                          <p class="text-[15px] font-bold tracking-tight">{{ formatCurrency(plan.installment_amount) }}</p>
                     </div>
                 </div>

                 <div class="flex justify-between items-end mb-2">
                     <div>
                         <p class="text-[11px] font-semibold text-[#a1a1aa] uppercase tracking-widest">Ejecución Fiscal: {{ plan.progress }}%</p>
                     </div>
                     <div class="text-right text-xs">
                         <span class="text-[#71717a]">Cubierto:</span> <span class="text-white font-bold ml-1 mr-4">{{ formatCurrency(plan.total_paid) }}</span>
                         <span class="text-[#71717a]">Déficit:</span> <span class="text-[#a1a1aa] font-bold ml-1">{{ formatCurrency(plan.remaining_balance) }}</span>
                     </div>
                 </div>
                 <div class="w-full h-[6px] bg-[#121212] rounded-full overflow-hidden border border-[#2a2a2a]">
                     <div class="h-full bg-white transition-all duration-700" :style="{ width: `${plan.progress}%` }"></div>
                 </div>
            </div>
        </div>

        <!-- Auditory Matrix -->
        <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden animate-slide-up" style="animation-delay: 200ms">
            <div class="px-6 py-4 border-b border-[#2a2a2a] bg-[#121212]">
                <h3 class="text-[10px] font-bold text-white tracking-widest uppercase">Matriz de Pagos Proyectados</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table-dark">
                    <thead>
                        <tr>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Periodo</th>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Punto Vigencia</th>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Cuantía Base</th>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Saldo Insoluto</th>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Fecha Consignación</th>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Diagnóstico</th>
                            <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Validación Operativa</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs">
                        <tr v-for="row in amortizationTable" :key="row.installment" 
                            class="border-[#2a2a2a] transition-colors"
                            :class="row.is_overdue ? 'bg-[#2a1313] hover:bg-[#3d1a1a]' : 'hover:bg-[#1e1e1e]'"
                        >
                            <td class="font-bold text-white"># {{ String(row.installment).padStart(3, '0') }}</td>
                            <td class="font-medium" :class="row.is_overdue ? 'text-rose-400 font-bold' : 'text-[#a1a1aa]'">{{ row.due_date }}</td>
                            <td class="font-semibold text-white tracking-wide">{{ formatCurrency(row.amount) }}</td>
                            <td class="text-[#71717a]">{{ formatCurrency(row.balance) }}</td>
                            <td class="text-[#a1a1aa] italic font-medium opacity-80 mt-[2px] inline-block">{{ row.paid_date || 'En descubierto' }}</td>
                            <td>
                                <span :class="['inline-block border px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest', getStatusClasses(row, row.installment === 1)]">
                                    {{ row.status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <button
                                        v-if="row.status !== 'paid'"
                                        @click="openPaymentModal(row)"
                                        :disabled="plan.reservation_status === 'pending_approval'"
                                        :class="plan.reservation_status === 'pending_approval' ? 'opacity-30 cursor-not-allowed border-[#2a2a2a] text-[#71717a]' : 'text-white hover:text-[#a1a1aa] border-[#3f3f46]'"
                                        :title="plan.reservation_status === 'pending_approval' ? 'Aprobación de reserva requerida para pagos' : 'Aportar Cuota'"
                                        class="text-[10px] uppercase font-bold tracking-widest transition-colors flex items-center gap-1 bg-[#262626] border px-3 py-1.5 rounded"
                                    >
                                        <v-icon name="md-add" scale="0.7"/> Aportar
                                    </button>
                                    <a
                                        v-if="row.status === 'paid'"
                                        :href="route('finances.payment.receipt', row.payment_id)"
                                        target="_blank"
                                        class="text-[10px] text-[#71717a] hover:text-white uppercase font-bold tracking-widest transition-colors flex items-center gap-2 border border-[#3f3f46] px-3 py-1.5 rounded bg-[#121212]"
                                    >
                                        <v-icon name="md-download" scale="0.8" />
                                        Extracto PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="payingPayment" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#000000] opacity-80 backdrop-blur-sm" @click="payingPayment = null"></div>
                    <div class="relative w-full max-w-md bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up shadow-2xl">
                        <h2 class="text-lg font-semibold text-white mb-2 tracking-tight">Conciliación de Periodo</h2>
                        <p class="text-xs text-[#71717a] mb-6 font-medium">
                            Obligación Nro. {{ String(payingPayment.installment).padStart(3, '0') }} • Exigible al {{ payingPayment.due_date }}
                        </p>

                        <form @submit.prevent="submitPayment" class="space-y-4">
                            <div>
                                <label class="label-dark text-[10px] uppercase font-bold tracking-wider">Forma de pago *</label>
                                <select v-model="paymentForm.payment_method" class="input-dark bg-[#121212] h-11">
                                    <option value="cash">Efectivo Físico</option>
                                    <option value="transfer">Transferencia</option>
                                    <option value="check">Cheque</option>
                                    <option value="other">Otro (especificar en las anotaciones)</option>
                                </select>
                            </div>
                            <div>
                                <label class="label-dark text-[10px] uppercase font-bold tracking-wider">Número de Referencia Interna</label>
                                <input v-model="paymentForm.reference_number" type="text" class="input-dark bg-[#121212] h-11" placeholder="Código de Transacción" />
                            </div>
                            <div>
                                <label class="label-dark text-[10px] uppercase font-bold tracking-wider">Anotaciones Contables</label>
                                <textarea v-model="paymentForm.notes" class="input-dark bg-[#121212]" rows="2" placeholder="Describir..."></textarea>
                            </div>

                            <div class="bg-white text-black rounded-xl p-5 mt-6 border border-[#2a2a2a]">
                                <p class="text-[10px] font-bold uppercase tracking-widest mb-1 opacity-60">Cobro Autorizado por App</p>
                                <p class="text-3xl font-bold tracking-tighter">{{ formatCurrency(payingPayment.amount) }}</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t border-[#2a2a2a] mt-6">
                                <button type="button" @click="payingPayment = null" class="btn-secondary">Cancelar</button>
                                <button type="submit" class="btn-primary" :disabled="paymentForm.processing">
                                    {{ paymentForm.processing ? 'Aplicando...' : 'Legalizar Cobro' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmModal
            :show="showCancelModal"
            title="¿Invalidar Contrato?"
            message="Esta acción es irreversible. Se cancelará el plan de pagos, se anulará la reserva y el lote volverá a estar DISPONIBLE para la venta."
            confirm-text="Sí, Invalidar"
            cancel-text="Cancelar"
            type="danger"
            @close="showCancelModal = false"
            @confirm="cancelPlan"
            :processing="cancelForm.processing"
        />
    </AppLayout>
</template>
