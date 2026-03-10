<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

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

const form = useForm({
    lot_id: props.lot.id,
    client_id: '',
    down_payment: '',
    payment_deadline: '',
    payment_proof: null,
    notes: '',
    total_installments: 12,
    start_date: new Date().toISOString().split('T')[0],
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
    openConfirm(
        'Liberar Inmueble',
        '¿Está seguro de cancelar esta reserva? El lote quedará disponible para la venta nuevamente de forma inmediata.',
        'Liberar Lote',
        'danger',
        () => router.post(route('reservations.cancel', props.reservation.id))
    );
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

const estimatedInstallment = computed(() => {
    if (!form.down_payment || !form.total_installments) return 0;
    const financed = props.lot.price - form.down_payment;
    return financed > 0 ? Math.round(financed / form.total_installments) : 0;
});
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
                        <span class="px-3 py-1 bg-[#1e1e1e] border border-[#3f3f46] rounded text-[10px] text-white font-semibold uppercase tracking-wider">
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
                            <p class="text-[10px] font-bold text-white uppercase tracking-wider">{{ reservation.status_label }}</p>
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
                                <option value="">--- Seleccionar del padrón ---</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.full_name }} ({{ client.document_number }})
                                </option>
                            </select>
                            <p v-if="form.errors.client_id" class="text-red-400 text-xs mt-1">{{ form.errors.client_id }}</p>
                            <Link :href="route('clients.create')" class="text-[10px] text-white underline mt-2 inline-block hover:opacity-75">
                                Registrar nuevo perfil
                            </Link>
                        </div>

                        <div class="grid grid-cols-2 gap-6 pt-4 border-t border-[#2a2a2a]">
                            <div>
                                <label class="label-dark">Enganche Pactado (COP)</label>
                                <input v-model="form.down_payment" type="number" class="input-dark bg-[#121212]" placeholder="0" />
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
                            <div class="grid grid-cols-3 gap-4 text-xs">
                                <div>
                                    <p class="text-[#71717a] mb-1">Valor Venta</p>
                                    <p class="text-white font-medium">{{ formatCurrency(lot.price) }}</p>
                                </div>
                                <div>
                                    <p class="text-[#71717a] mb-1">Monto Financiable</p>
                                    <p class="text-white font-medium">{{ formatCurrency(lot.price - form.down_payment) }}</p>
                                </div>
                                <div>
                                    <p class="text-[#71717a] mb-1">Mensualidad Fija</p>
                                    <p class="text-white font-bold">{{ formatCurrency(estimatedInstallment) }}</p>
                                </div>
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
