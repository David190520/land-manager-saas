<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import CreateClientModal from '@/Pages/Clients/Partials/CreateClientModal.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const props = defineProps({
    lot: Object,
    reservation: Object,
    clients: Array,
});

const page = usePage();
const isAdmin = computed(() => {
    return page.props.auth.user.role === 'admin' || page.props.auth.user.role === 'accountant';
});

const showReservationForm = ref(false);
const showCreateClientModal = ref(false);
const showEditLotModal = ref(false);
const showCancelRefundModal = ref(false);
const refundDownPayment = ref(false);

const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    type: 'info',
    action: null
});

const openConfirm = (title, message, confirmText, type, action) => {
    confirmDialog.value = { show: true, title, message, confirmText, type, action };
};

const closeConfirm = () => {
    confirmDialog.value.show = false;
};

const executeConfirm = () => {
    if (confirmDialog.value.action) confirmDialog.value.action();
    closeConfirm();
};

const lotForm = useForm({
    area: props.lot.area,
    front_length: props.lot.front_length,
    depth_length: props.lot.depth_length,
    price: props.lot.price,
    notes: props.lot.notes,
});

const calculateArea = () => {
    if (lotForm.front_length && lotForm.depth_length) {
        lotForm.area = lotForm.front_length * lotForm.depth_length;
        calculatePrice();
    }
};

const calculatePrice = () => {
    if (lotForm.area && props.lot.project.price_per_m2) {
        lotForm.price = lotForm.area * props.lot.project.price_per_m2;
    }
};

const submitEditLot = () => {
    lotForm.put(route('lots.update', props.lot.id), {
        onSuccess: () => showEditLotModal.value = false,
        preserveScroll: true,
    });
};

const thirtyDaysFromNow = new Date();
thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);

const form = useForm({
    lot_id: props.lot.id,
    client_id: '',
    down_payment: '',
    payment_deadline: '',
    payment_proof: null,
    notes: '',
    total_installments: 12,
    start_date: new Date().toISOString().split('T')[0],
    initial_payment_percentage: 30,
    initial_payment_deadline: thirtyDaysFromNow.toISOString().split('T')[0],
});

const submitReservation = () => {
    form.post(route('reservations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showReservationForm.value = false;
            form.reset();
        },
    });
};

const handleProofUpload = (event) => {
    form.payment_proof = event.target.files[0];
};

const cancelReservation = () => {
    // If initial payment hasn't been paid, ask about refunding the down payment
    if (props.reservation?.payment_plan && !props.reservation.payment_plan.initial_payment_paid) {
        refundDownPayment.value = false;
        showCancelRefundModal.value = true;
    } else {
        openConfirm(
            'Liberar Inmueble',
            '¿Está seguro de cancelar esta reserva? El lote quedará disponible para la venta nuevamente de forma inmediata.',
            'Liberar Lote',
            'danger',
            () => router.post(route('reservations.cancel', props.reservation.id))
        );
    }
};

const executeCancelWithRefund = () => {
    router.post(route('reservations.cancel', props.reservation.id), {
        refund_down_payment: refundDownPayment.value,
    });
    showCancelRefundModal.value = false;
};

const confirmReservation = () => {
    openConfirm(
        'Consolidar Venta',
        '¿Desea marcar este lote como vendido definitivamente? Esta acción registrará la propiedad a nombre del cliente.',
        'Confirmar Venta',
        'info',
        () => router.post(route('reservations.confirm', props.reservation.id))
    );
};

const approveReservation = () => {
    openConfirm(
        'Aprobar Solicitud',
        'Al aprobar esta reserva, el lote pasará a estado Reservado y se activará el plan de pagos correspondiente.',
        'Aprobar Ahora',
        'success',
        () => router.post(route('reservations.approve', props.reservation.id))
    );
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    }).format(value);
};

const initialPaymentAmount = computed(() => {
    return Math.round(props.lot.price * (form.initial_payment_percentage / 100));
});

const estimatedInstallment = computed(() => {
    if (!form.down_payment || !form.total_installments) return 0;
    const financed = props.lot.price - form.down_payment - initialPaymentAmount.value;
    return financed > 0 ? Math.round(financed / form.total_installments) : 0;
});

const getStatusBadgeClasses = (status) => {
    return {
        'available': 'bg-[#18181a] border-[#2a2a2a] text-white',
        'reserved': 'bg-blue-500/10 border-blue-500/20 text-blue-500',
        'sold': 'bg-red-500/10 border-red-500/20 text-red-500',
        'pending_approval': 'bg-amber-500/10 border-amber-500/20 text-amber-500',
    }[status] || 'bg-[#1e1e1e] border-[#3f3f46] text-white';
};

const getStatusTextClasses = (status) => {
    return {
        'available': 'text-white',
        'reserved': 'text-blue-500',
        'sold': 'text-red-500',
        'pending_approval': 'text-amber-500',
        'active': 'text-green-500',
        'confirmed': 'text-blue-500',
        'cancelled': 'text-red-500',
    }[status] || 'text-white';
};
</script>

<template>
    <AppLayout>
        <Head :title="`Lote ${lot.lot_number} - ${lot.block.name}`" />

        <ConfirmModal
            :show="confirmDialog.show"
            :title="confirmDialog.title"
            :message="confirmDialog.message"
            :confirmText="confirmDialog.confirmText"
            :type="confirmDialog.type"
            @close="closeConfirm"
            @confirm="executeConfirm"
        />

        <!-- Cancel with refund modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showCancelRefundModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCancelRefundModal = false"></div>
                    <div class="relative w-full max-w-md bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up shadow-2xl">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#2a2a2a]">
                            <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center">
                                <v-icon name="md-cancel-outlined" scale="1.1" fill="#f43f5e" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-white">Liberar Inmueble</h2>
                                <p class="text-[10px] text-[#71717a] uppercase tracking-wider font-medium">Cuota inicial no registrada</p>
                            </div>
                        </div>

                        <p class="text-xs text-[#a1a1aa] mb-6 leading-relaxed">
                            La cuota inicial de este contrato aún no ha sido pagada. Al cancelar, el lote quedará disponible nuevamente.
                        </p>

                        <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-4 mb-6">
                            <p class="text-[10px] text-[#71717a] uppercase tracking-wider font-bold mb-3">Apartado recibido</p>
                            <p class="text-xl font-bold text-white">{{ formatCurrency(reservation.down_payment) }}</p>
                            <label class="flex items-center gap-3 mt-4 cursor-pointer group">
                                <input type="checkbox" v-model="refundDownPayment" class="w-4 h-4 rounded border-[#3f3f46] bg-[#18181a] text-white" />
                                <span class="text-xs text-[#a1a1aa] group-hover:text-white transition-colors">Autorizar devolución del apartado al cliente</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showCancelRefundModal = false" class="btn-secondary">Mantener Reserva</button>
                            <button type="button" @click="executeCancelWithRefund" class="bg-rose-600 hover:bg-rose-500 text-white text-[10px] font-bold uppercase tracking-widest px-4 py-2 rounded transition-colors">
                                Liberar Lote
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Modal :show="showEditLotModal" maxWidth="md" @close="showEditLotModal = false">
            <div class="p-8 relative overflow-hidden bg-[#18181a]">
                <div class="flex items-center justify-between mb-8 pb-4 border-b border-[#2a2a2a]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#262626] border border-[#3f3f46] flex items-center justify-center">
                            <v-icon name="md-edit-outlined" scale="1.1" fill="white" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white tracking-tight">Editar Lote</h2>
                            <p class="text-[10px] text-[#71717a] font-bold uppercase tracking-widest">Dimensiones y Precio</p>
                        </div>
                    </div>
                    <button @click="showEditLotModal = false" class="p-2 text-[#71717a] hover:text-white transition-colors">
                        <v-icon name="md-close" scale="1.2" />
                    </button>
                </div>

                <form @submit.prevent="submitEditLot" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label-dark">Frente (mts)</label>
                            <input v-model="lotForm.front_length" @input="calculateArea" type="number" step="0.01" class="input-dark bg-[#121212]" placeholder="Ej: 10" />
                        </div>
                        <div>
                            <label class="label-dark">Fondo (mts)</label>
                            <input v-model="lotForm.depth_length" @input="calculateArea" type="number" step="0.01" class="input-dark bg-[#121212]" placeholder="Ej: 20" />
                        </div>
                    </div>

                    <div>
                        <label class="label-dark">Área Total M²</label>
                        <div class="flex gap-2">
                            <CurrencyInput v-model="lotForm.area" @input="calculatePrice" placeholder="Ej: 200" />
                            <button type="button" @click="calculatePrice" class="btn-secondary px-3" title="Calcular Precio" v-if="props.lot.project.price_per_m2">
                                <v-icon name="md-calculate-outlined" scale="1.2" />
                            </button>
                        </div>
                        <p class="text-[10px] text-[#71717a] mt-1" v-if="props.lot.project.price_per_m2">Base calculable: {{ formatCurrency(props.lot.project.price_per_m2) }} / m²</p>
                    </div>

                    <div>
                        <label class="label-dark">Precio Base (COP)</label>
                        <CurrencyInput v-model="lotForm.price" prefix="$" placeholder="Valor total" />
                    </div>

                    <div>
                        <label class="label-dark">Notas internas (Morfología, esquina, etc)</label>
                        <textarea v-model="lotForm.notes" rows="2" class="input-dark bg-[#121212]"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-[#2a2a2a]">
                        <button type="button" @click="showEditLotModal = false" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="lotForm.processing">
                            {{ lotForm.processing ? 'Guardando...' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <CreateClientModal
            :show="showCreateClientModal"
            @close="showCreateClientModal = false"
        />

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-[#71717a] mb-8 animate-fade-in font-medium">
            <Link :href="route('projects.index')" class="hover:text-white transition-colors">Proyectos</Link>
            <v-icon name="md-keyboardarrowright" scale="0.8" />
            <Link :href="route('projects.show', lot.project.id)" class="hover:text-white transition-colors">{{ lot.project.name }}</Link>
            <v-icon name="md-keyboardarrowright" scale="0.8" />
            <span class="text-[#ededed]">{{ lot.block.name }} - Lote {{ lot.lot_number }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-8">
            <!-- Lot Details -->
            <div class="space-y-6">
                <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up">
                    <div class="flex items-center justify-between mb-8 pb-4 border-b border-[#2a2a2a]">
                        <h2 class="text-2xl font-semibold text-white tracking-tight">Lote {{ lot.lot_number }}</h2>
                        <span :class="['px-3 py-1 rounded text-[10px] font-semibold uppercase tracking-wider border', getStatusBadgeClasses(lot.status)]">
                            {{ lot.status_label }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[11px] uppercase tracking-wider text-[#71717a] font-semibold">Manzana</span>
                            <span class="text-xs font-semibold text-white">{{ lot.block.name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[11px] uppercase tracking-wider text-[#71717a] font-semibold">Área</span>
                            <span class="text-xs font-semibold text-white">{{ lot.area }} m²</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[11px] uppercase tracking-wider text-[#71717a] font-semibold">Dimensiones</span>
                            <span class="text-[10px] font-medium text-[#a1a1aa]">{{ lot.front_length }}m x {{ lot.depth_length }}m</span>
                        </div>
                        <div class="flex justify-between items-center py-4 border-t border-[#2a2a2a] mt-4">
                            <span class="text-xs text-[#71717a] uppercase tracking-wider font-semibold">Precio Base</span>
                            <span class="text-xl font-bold text-white tracking-tight">{{ formatCurrency(lot.price) }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 space-y-3">
                        <button
                            v-if="lot.status === 'available'"
                            @click="showReservationForm = true"
                            class="w-full btn-primary py-3 flex items-center justify-center gap-2"
                        >
                            <v-icon name="md-add" fill="black" />
                            <span>Reservar Inmueble</span>
                        </button>
                        <button
                            v-if="isAdmin"
                            @click="showEditLotModal = true"
                            class="w-full btn-secondary py-3 flex items-center justify-center gap-2"
                        >
                            <v-icon name="md-edit-outlined" />
                            <span>Editar Lote</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reservation / Form Area -->
            <div class="space-y-8">
                <!-- Active Reservation -->
                <div v-if="reservation" class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up" style="animation-delay: 100ms">
                    <h3 class="text-sm font-semibold text-white mb-6 uppercase tracking-wider pb-3 border-b border-[#2a2a2a] flex items-center gap-3">
                        <v-icon name="md-peopleoutline" scale="1.2" fill="#ededed" />
                        Detalles de Reserva
                    </h3>

                    <!-- Client Info -->
                    <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-5 mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-[#1e1e1e] flex items-center justify-center text-white font-bold border border-[#3f3f46]">
                                {{ reservation.client.full_name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm text-white font-semibold">{{ reservation.client.full_name }}</p>
                                <p class="text-[10px] text-[#71717a] uppercase mt-1 tracking-wider">{{ reservation.client.document_number }} • {{ reservation.client.phone }}</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-[#2a2a2a]" v-if="reservation.payment_proof">
                            <a :href="`/storage/${reservation.payment_proof}`" target="_blank" class="text-xs text-blue-400 hover:underline flex items-center gap-1">
                                <v-icon name="md-attachfile" scale="0.8" />
                                Ver Comprobante de Pago
                            </a>
                        </div>
                    </div>

                    <!-- Reservation Details Table -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-0 mb-8 border border-[#2a2a2a] rounded-xl overflow-hidden bg-[#121212]">
                        <div class="p-4 border-r border-b lg:border-b-0 border-[#2a2a2a]">
                            <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-2">Enganche</p>
                            <p class="text-sm font-bold text-white">{{ formatCurrency(reservation.down_payment) }}</p>
                        </div>
                        <div class="p-4 border-r border-b lg:border-b-0 border-[#2a2a2a]">
                            <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-2">Vence</p>
                            <p class="text-sm font-bold text-white">{{ reservation.payment_deadline }}</p>
                        </div>
                        <div class="p-4 border-r border-b lg:border-b-0 border-[#2a2a2a]">
                            <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-2">Estado</p>
                            <p :class="['text-[10px] font-bold uppercase tracking-wider', getStatusTextClasses(reservation.status)]">{{ reservation.status_label }}</p>
                        </div>
                        <div class="p-4 border-r border-[#2a2a2a]">
                            <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-2">Fecha Ops</p>
                            <p class="text-xs font-semibold text-[#a1a1aa]">{{ reservation.created_at.split('T')[0] }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-2">Asesor</p>
                            <p class="text-xs font-semibold text-white truncate">{{ reservation.agent.name }}</p>
                        </div>
                    </div>

                    <!-- Initial Payment Pending Warning -->
                    <div v-if="reservation.payment_plan && !reservation.payment_plan.initial_payment_paid" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mb-6 flex items-center gap-3">
                        <v-icon name="md-warningamber-outlined" scale="1.2" fill="#f59e0b" class="shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-amber-400">Cuota inicial pendiente</p>
                            <p class="text-[10px] text-amber-500/80 mt-0.5">
                                {{ formatCurrency(reservation.payment_plan.initial_payment_amount) }} — Vence: {{ reservation.payment_plan.initial_payment_deadline }}
                            </p>
                        </div>
                    </div>

                    <!-- Payment Plan Summary -->
                    <div v-if="reservation.payment_plan" class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-6 mb-8 mt-6">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-sm font-semibold text-white tracking-wide">Amortización</h4>
                            <Link :href="route('finances.plan', reservation.payment_plan.id)" class="text-[10px] uppercase font-bold text-white bg-[#1e1e1e] px-3 py-1.5 rounded border border-[#3f3f46] hover:bg-[#262626] transition-colors">
                                Detalles de Plan
                            </Link>
                        </div>
                        <div class="grid grid-cols-3 gap-6 mb-5">
                            <div>
                                <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-1">A Financiar</p>
                                <p class="text-sm font-bold text-white">{{ formatCurrency(reservation.payment_plan.financed_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-1">Saldada</p>
                                <p class="text-sm font-bold text-white">{{ formatCurrency(reservation.payment_plan.total_paid) }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] text-[#71717a] uppercase tracking-wider mb-1">Remanente</p>
                                <p class="text-sm font-bold text-[#a1a1aa]">{{ formatCurrency(reservation.payment_plan.remaining_balance) }}</p>
                            </div>
                        </div>
                        <div class="w-full h-[3px] bg-[#1e1e1e] rounded overflow-hidden">
                            <div class="h-full bg-white transition-all duration-500"
                                 :style="{ width: `${reservation.payment_plan.progress}%` }"></div>
                        </div>
                        <p class="text-[9px] uppercase tracking-wider text-[#71717a] mt-3">{{ reservation.payment_plan.progress }}% Cubierto</p>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button v-if="reservation.status === 'pending_approval' && isAdmin" @click="approveReservation" class="btn-primary flex-1 bg-green-600 hover:bg-green-500 text-white">
                            Aprobar Reserva
                        </button>
                        <button v-if="reservation.status === 'active' && isAdmin" @click="confirmReservation" class="btn-primary flex-1">
                            Confirmar Propiedad
                        </button>
                        <button v-if="['active', 'pending_approval'].includes(reservation.status) && isAdmin" @click="cancelReservation" class="btn-secondary flex-1">
                            Liberar Lote
                        </button>
                    </div>
                </div>

                <!-- Reservation Form -->
                <div v-if="showReservationForm && lot.status === 'available'" class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-semibold text-white tracking-tight">Formulario de Reserva</h3>
                        <button @click="showReservationForm = false" class="text-[#71717a] hover:text-white">
                            <v-icon name="md-close" />
                        </button>
                    </div>

                    <form @submit.prevent="submitReservation" class="space-y-6" enctype="multipart/form-data">
                        <!-- Client Selection -->
                        <div>
                            <label class="label-dark">Seleccione Cliente Titular</label>
                            <select v-model="form.client_id" class="input-dark bg-[#121212]">
                                <option value="">Seleccionar cliente</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.full_name }} ({{ client.document_number }})
                                </option>
                            </select>
                            <p v-if="form.errors.client_id" class="text-red-400 text-xs mt-1">{{ form.errors.client_id }}</p>
                            <button type="button" @click="showCreateClientModal = true" class="text-[10px] text-white underline mt-2 inline-block hover:opacity-75">
                                Registrar nuevo perfil
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-6 pt-4 border-t border-[#2a2a2a]">
                            <div>
                                <label class="label-dark">Enganche Pactado (COP)</label>
                                <CurrencyInput v-model="form.down_payment" prefix="$" placeholder="0" />
                                <p v-if="form.errors.down_payment" class="text-red-400 text-xs mt-1">{{ form.errors.down_payment }}</p>
                            </div>
                            <div>
                                <label class="label-dark">Límite de Consignación</label>
                                <input v-model="form.payment_deadline" type="date" class="input-dark bg-[#121212]" />
                                <p v-if="form.errors.payment_deadline" class="text-red-400 text-xs mt-1">{{ form.errors.payment_deadline }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="label-dark">Comprobante de Pago (PDF/Imagen)</label>
                            <input type="file" @change="handleProofUpload" accept=".pdf,image/*" class="input-dark bg-[#121212] py-2" />
                            <p v-if="form.errors.payment_proof" class="text-red-400 text-xs mt-1">{{ form.errors.payment_proof }}</p>
                        </div>

                        <!-- Cuota Inicial -->
                        <div class="pt-4 border-t border-[#2a2a2a]">
                            <p class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-4">Cuota Inicial (30%)</p>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="label-dark">Porcentaje (%)</label>
                                    <input v-model="form.initial_payment_percentage" type="number" min="1" max="100" step="0.5" class="input-dark bg-[#121212]" />
                                    <p v-if="form.errors.initial_payment_percentage" class="text-red-400 text-xs mt-1">{{ form.errors.initial_payment_percentage }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Fecha Límite Cuota Inicial</label>
                                    <input v-model="form.initial_payment_deadline" type="date" class="input-dark bg-[#121212]" />
                                    <p v-if="form.errors.initial_payment_deadline" class="text-red-400 text-xs mt-1">{{ form.errors.initial_payment_deadline }}</p>
                                </div>
                            </div>
                            <div class="mt-3 bg-amber-500/10 border border-amber-500/20 rounded-lg px-4 py-3 flex items-center justify-between">
                                <span class="text-[10px] text-amber-400 font-semibold uppercase tracking-wider">Valor de Cuota Inicial</span>
                                <span class="text-sm font-bold text-amber-300">{{ formatCurrency(initialPaymentAmount) }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="label-dark">Plazo (Meses)</label>
                                <input v-model="form.total_installments" type="number" min="1" max="120" class="input-dark bg-[#121212]" />
                                <p v-if="form.errors.total_installments" class="text-red-400 text-xs mt-1">{{ form.errors.total_installments }}</p>
                            </div>
                            <div>
                                <label class="label-dark">Inicio Corriente</label>
                                <input v-model="form.start_date" type="date" class="input-dark bg-[#121212]" />
                                <p v-if="form.errors.start_date" class="text-red-400 text-xs mt-1">{{ form.errors.start_date }}</p>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div v-if="form.down_payment && form.total_installments" class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-5 mt-6">
                            <p class="text-[10px] font-bold text-white uppercase tracking-widest mb-4">Proyección</p>
                            <div class="grid grid-cols-2 gap-4 text-xs mb-3">
                                <div>
                                    <p class="text-[#71717a] mb-1">Valor Venta</p>
                                    <p class="text-white font-medium">{{ formatCurrency(lot.price) }}</p>
                                </div>
                                <div>
                                    <p class="text-[#71717a] mb-1">Enganche</p>
                                    <p class="text-white font-medium">{{ formatCurrency(form.down_payment) }}</p>
                                </div>
                                <div>
                                    <p class="text-[#71717a] mb-1">Cuota Inicial ({{ form.initial_payment_percentage }}%)</p>
                                    <p class="text-amber-300 font-medium">{{ formatCurrency(initialPaymentAmount) }}</p>
                                </div>
                                <div>
                                    <p class="text-[#71717a] mb-1">Monto Financiable</p>
                                    <p class="text-white font-medium">{{ formatCurrency(Math.max(0, lot.price - form.down_payment - initialPaymentAmount)) }}</p>
                                </div>
                            </div>
                            <div class="border-t border-[#2a2a2a] pt-3 mt-1">
                                <p class="text-[#71717a] text-xs mb-1">Mensualidad Fija</p>
                                <p class="text-white font-bold text-base">{{ formatCurrency(estimatedInstallment) }}</p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <label class="label-dark">Anotaciones del Contrato</label>
                            <textarea v-model="form.notes" class="input-dark bg-[#121212]" rows="2" placeholder="Términos extra..."></textarea>
                        </div>

                        <div class="pt-6">
                            <button type="submit" class="w-full btn-primary py-3" :disabled="form.processing">
                                {{ form.processing ? 'Registrando...' : 'Emitir Documento de Reserva' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
