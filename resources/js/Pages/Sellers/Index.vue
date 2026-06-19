<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    sellers: Array,
    commissions: Object,
    filters: Object,
});

// ── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('sellers');

// ── Add seller form ───────────────────────────────────────────────────────────
const showAddForm = ref(false);
const addForm = useForm({
    full_name: '',
    document_number: '',
    phone: '',
    email: '',
    commission_rate: 3.00,
});

const submitAdd = () => {
    addForm.post(route('sellers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddForm.value = false;
            addForm.reset();
        },
    });
};

// ── Edit seller ───────────────────────────────────────────────────────────────
const editingSeller = ref(null);
const editForm = useForm({
    full_name: '',
    document_number: '',
    phone: '',
    email: '',
    commission_rate: 3.00,
    is_active: true,
});

const startEdit = (seller) => {
    editingSeller.value = seller.id;
    editForm.full_name = seller.full_name;
    editForm.document_number = seller.document_number ?? '';
    editForm.phone = seller.phone ?? '';
    editForm.email = seller.email ?? '';
    editForm.commission_rate = seller.commission_rate;
    editForm.is_active = seller.is_active;
};

const cancelEdit = () => {
    editingSeller.value = null;
    editForm.reset();
};

const submitEdit = (sellerId) => {
    editForm.put(route('sellers.update', sellerId), {
        preserveScroll: true,
        onSuccess: () => {
            editingSeller.value = null;
        },
    });
};

const deleteSeller = (seller) => {
    if (!confirm(`¿Eliminar al vendedor "${seller.full_name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(route('sellers.destroy', seller.id), { preserveScroll: true });
};

// ── Commissions ───────────────────────────────────────────────────────────────
const filterSellerId = ref(props.filters?.seller_id ?? '');
const filterStatus = ref(props.filters?.status ?? '');

const applyFilters = () => {
    router.get(route('sellers.index'), {
        seller_id: filterSellerId.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const showPayModal = ref(false);
const selectedCommission = ref(null);
const payForm = useForm({ notes: '' });

const openPayModal = (commission) => {
    selectedCommission.value = commission;
    payForm.reset();
    showPayModal.value = true;
};

const submitPay = () => {
    payForm.post(route('commissions.pay', selectedCommission.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPayModal.value = false;
            selectedCommission.value = null;
        },
    });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatCurrency = (value) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);

const totalPending = computed(() =>
    (props.commissions?.data ?? [])
        .filter(c => c.status === 'pending')
        .reduce((sum, c) => sum + c.commission_amount, 0)
);
</script>

<template>
    <AppLayout>
        <Head title="Personal y Comisiones" />

        <!-- Pay commission modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showPayModal = false"></div>
                    <div class="relative w-full max-w-md bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl shadow-2xl">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#2a2a2a]">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center">
                                <v-icon name="md-paid-outlined" scale="1.1" fill="#10b981" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-white">Registrar Pago de Comisión</h2>
                                <p class="text-[10px] text-[#71717a] uppercase tracking-wider font-medium">{{ selectedCommission?.seller_name }}</p>
                            </div>
                        </div>

                        <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-4 mb-6">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[10px] text-[#71717a] uppercase tracking-wider">Valor de comisión</span>
                                <span class="text-xl font-bold text-emerald-400">{{ formatCurrency(selectedCommission?.commission_amount) }}</span>
                            </div>
                            <div class="text-[10px] text-[#71717a] space-y-1">
                                <p>Cliente: <span class="text-white">{{ selectedCommission?.client_name }}</span></p>
                                <p>Lote: <span class="text-white">{{ selectedCommission?.lot }}</span></p>
                                <p>Base: <span class="text-white">{{ formatCurrency(selectedCommission?.base_amount) }} × {{ selectedCommission?.commission_rate }}%</span></p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="label-dark">Notas (opcional)</label>
                            <textarea v-model="payForm.notes" rows="2" class="input-dark bg-[#121212]" placeholder="Método de pago, referencia, etc."></textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showPayModal = false" class="btn-secondary">Cancelar</button>
                            <button type="button" @click="submitPay" :disabled="payForm.processing" class="bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-widest px-5 py-2 rounded transition-colors disabled:opacity-50">
                                Confirmar Pago
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Header -->
        <div class="flex items-center justify-between mb-8 animate-fade-in">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Personal y Comisiones</h1>
                <p class="text-xs text-[#71717a] mt-1">Gestión de vendedores y seguimiento de comisiones generadas</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 mb-8 bg-[#1e1e1e] rounded-xl p-1 border border-[#2a2a2a] w-fit">
            <button
                @click="activeTab = 'sellers'"
                :class="['px-5 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest transition-all', activeTab === 'sellers' ? 'bg-white text-black' : 'text-[#71717a] hover:text-white']"
            >
                Vendedores ({{ sellers.length }})
            </button>
            <button
                @click="activeTab = 'commissions'"
                :class="['px-5 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest transition-all', activeTab === 'commissions' ? 'bg-white text-black' : 'text-[#71717a] hover:text-white']"
            >
                Comisiones
            </button>
        </div>

        <!-- ── VENDEDORES TAB ── -->
        <div v-if="activeTab === 'sellers'" class="space-y-6 animate-fade-in">
            <!-- Add seller button / form -->
            <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden">
                <div class="px-6 py-4 flex items-center justify-between border-b border-[#2a2a2a]">
                    <h2 class="text-[11px] font-bold text-white uppercase tracking-widest">Equipo de Ventas</h2>
                    <button @click="showAddForm = !showAddForm" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest bg-white text-black px-4 py-2 rounded-lg hover:bg-[#e5e5e5] transition-colors">
                        <v-icon :name="showAddForm ? 'md-close' : 'md-add'" scale="0.85" />
                        {{ showAddForm ? 'Cancelar' : 'Nuevo Vendedor' }}
                    </button>
                </div>

                <!-- Add form -->
                <div v-if="showAddForm" class="px-6 py-6 border-b border-[#2a2a2a] bg-[#121212]">
                    <form @submit.prevent="submitAdd" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="label-dark">Nombre Completo *</label>
                            <input v-model="addForm.full_name" type="text" class="input-dark bg-[#18181a]" placeholder="Ej: Juan Pérez" required />
                            <p v-if="addForm.errors.full_name" class="text-red-400 text-xs mt-1">{{ addForm.errors.full_name }}</p>
                        </div>
                        <div>
                            <label class="label-dark">Cédula</label>
                            <input v-model="addForm.document_number" type="text" class="input-dark bg-[#18181a]" placeholder="Número de documento" />
                        </div>
                        <div>
                            <label class="label-dark">Teléfono</label>
                            <input v-model="addForm.phone" type="text" class="input-dark bg-[#18181a]" placeholder="Celular" />
                        </div>
                        <div>
                            <label class="label-dark">Correo Electrónico</label>
                            <input v-model="addForm.email" type="email" class="input-dark bg-[#18181a]" placeholder="vendedor@email.com" />
                            <p v-if="addForm.errors.email" class="text-red-400 text-xs mt-1">{{ addForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="label-dark">% Comisión *</label>
                            <input v-model="addForm.commission_rate" type="number" min="0" max="100" step="0.25" class="input-dark bg-[#18181a]" />
                            <p v-if="addForm.errors.commission_rate" class="text-red-400 text-xs mt-1">{{ addForm.errors.commission_rate }}</p>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full btn-primary py-2.5" :disabled="addForm.processing">
                                {{ addForm.processing ? 'Guardando...' : 'Registrar Vendedor' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sellers table -->
                <div v-if="sellers.length === 0" class="text-center py-16">
                    <v-icon name="md-recentactors-outlined" scale="2" fill="#3f3f46" />
                    <p class="text-xs text-[#71717a] mt-4">No hay vendedores registrados aún.</p>
                </div>

                <div v-else class="divide-y divide-[#2a2a2a]">
                    <div v-for="seller in sellers" :key="seller.id">
                        <!-- View row -->
                        <div v-if="editingSeller !== seller.id" class="px-6 py-4 flex items-center gap-4">
                            <div class="w-9 h-9 rounded-full bg-[#262626] border border-[#3f3f46] flex items-center justify-center text-white font-bold text-sm shrink-0">
                                {{ seller.full_name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-white truncate">{{ seller.full_name }}</p>
                                    <span v-if="!seller.is_active" class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-[#3f3f46] text-[#71717a]">Inactivo</span>
                                </div>
                                <p class="text-[10px] text-[#71717a] mt-0.5">
                                    <span v-if="seller.document_number">CC {{ seller.document_number }} •&nbsp;</span>
                                    <span v-if="seller.phone">{{ seller.phone }}</span>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-emerald-400">{{ seller.commission_rate }}%</p>
                                <p class="text-[9px] text-[#71717a] mt-0.5">{{ seller.commissions_count }} comisión{{ seller.commissions_count !== 1 ? 'es' : '' }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button @click="startEdit(seller)" class="p-2 text-[#71717a] hover:text-white transition-colors" title="Editar">
                                    <v-icon name="md-edit-outlined" scale="0.9" />
                                </button>
                                <button @click="deleteSeller(seller)" class="p-2 text-[#71717a] hover:text-rose-400 transition-colors" title="Eliminar">
                                    <v-icon name="md-deleteoutline" scale="0.9" />
                                </button>
                            </div>
                        </div>

                        <!-- Edit row -->
                        <div v-else class="px-6 py-4 bg-[#121212]">
                            <form @submit.prevent="submitEdit(seller.id)" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="label-dark">Nombre *</label>
                                    <input v-model="editForm.full_name" type="text" class="input-dark bg-[#18181a]" required />
                                    <p v-if="editForm.errors.full_name" class="text-red-400 text-xs mt-1">{{ editForm.errors.full_name }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Cédula</label>
                                    <input v-model="editForm.document_number" type="text" class="input-dark bg-[#18181a]" />
                                </div>
                                <div>
                                    <label class="label-dark">Teléfono</label>
                                    <input v-model="editForm.phone" type="text" class="input-dark bg-[#18181a]" />
                                </div>
                                <div>
                                    <label class="label-dark">Email</label>
                                    <input v-model="editForm.email" type="email" class="input-dark bg-[#18181a]" />
                                </div>
                                <div>
                                    <label class="label-dark">% Comisión *</label>
                                    <input v-model="editForm.commission_rate" type="number" min="0" max="100" step="0.25" class="input-dark bg-[#18181a]" />
                                </div>
                                <div class="flex items-end gap-2">
                                    <label class="flex items-center gap-2 cursor-pointer mb-2">
                                        <input type="checkbox" v-model="editForm.is_active" class="w-4 h-4 rounded border-[#3f3f46] bg-[#18181a]" />
                                        <span class="text-xs text-[#a1a1aa]">Activo</span>
                                    </label>
                                </div>
                                <div class="md:col-span-3 flex justify-end gap-3">
                                    <button type="button" @click="cancelEdit" class="btn-secondary">Cancelar</button>
                                    <button type="submit" class="btn-primary px-6" :disabled="editForm.processing">
                                        {{ editForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── COMISIONES TAB ── -->
        <div v-if="activeTab === 'commissions'" class="space-y-6 animate-fade-in">
            <!-- Summary + Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-[#18181a] border border-[#2a2a2a] rounded-xl p-5">
                    <p class="text-[9px] text-[#71717a] uppercase tracking-widest mb-2">Total por Pagar (página)</p>
                    <p class="text-xl font-bold text-amber-400">{{ formatCurrency(totalPending) }}</p>
                </div>
                <div class="md:col-span-2 bg-[#18181a] border border-[#2a2a2a] rounded-xl p-5 flex items-end gap-4">
                    <div class="flex-1">
                        <label class="label-dark">Vendedor</label>
                        <select v-model="filterSellerId" class="input-dark bg-[#121212]">
                            <option value="">Todos</option>
                            <option v-for="seller in sellers" :key="seller.id" :value="seller.id">{{ seller.full_name }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="label-dark">Estado</label>
                        <select v-model="filterStatus" class="input-dark bg-[#121212]">
                            <option value="">Todos</option>
                            <option value="pending">Pendiente</option>
                            <option value="paid">Pagada</option>
                        </select>
                    </div>
                    <button @click="applyFilters" class="btn-primary px-5 py-2.5 shrink-0">Filtrar</button>
                </div>
            </div>

            <!-- Commissions table -->
            <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-[#2a2a2a]">
                    <h2 class="text-[11px] font-bold text-white uppercase tracking-widest">Registro de Comisiones</h2>
                </div>

                <div v-if="commissions.data.length === 0" class="text-center py-16">
                    <v-icon name="md-paid-outlined" scale="2" fill="#3f3f46" />
                    <p class="text-xs text-[#71717a] mt-4">No hay comisiones generadas aún.</p>
                    <p class="text-[10px] text-[#3f3f46] mt-1">Las comisiones se generan automáticamente cuando un contrato es completado.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-[#2a2a2a]">
                                <th class="px-6 py-3 text-left text-[9px] font-bold text-[#71717a] uppercase tracking-wider">Vendedor</th>
                                <th class="px-6 py-3 text-left text-[9px] font-bold text-[#71717a] uppercase tracking-wider">Cliente / Lote</th>
                                <th class="px-6 py-3 text-right text-[9px] font-bold text-[#71717a] uppercase tracking-wider">Base</th>
                                <th class="px-6 py-3 text-right text-[9px] font-bold text-[#71717a] uppercase tracking-wider">Comisión</th>
                                <th class="px-6 py-3 text-center text-[9px] font-bold text-[#71717a] uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-[9px] font-bold text-[#71717a] uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2a2a2a]">
                            <tr v-for="c in commissions.data" :key="c.id" class="hover:bg-[#1e1e1e] transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-white">{{ c.seller_name }}</p>
                                    <p class="text-[9px] text-[#71717a]">{{ c.commission_rate }}% de comisión</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-[#a1a1aa]">{{ c.client_name }}</p>
                                    <p class="text-[9px] text-[#71717a]">{{ c.lot }} — {{ c.project }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="font-semibold text-[#a1a1aa]">{{ formatCurrency(c.base_amount) }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="font-bold text-emerald-400">{{ formatCurrency(c.commission_amount) }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="c.status === 'paid'" class="inline-flex items-center gap-1 px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                                        <v-icon name="md-checkcircleoutline" scale="0.7" fill="#10b981" />
                                        Pagada
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-500/10 border border-amber-500/30 text-amber-400">
                                        <v-icon name="md-scheduleoutlined" scale="0.7" fill="#f59e0b" />
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <p class="text-[#a1a1aa]">{{ c.created_at }}</p>
                                    <p v-if="c.paid_at" class="text-[9px] text-emerald-400 mt-0.5">Pagada: {{ c.paid_at }}</p>
                                    <p v-if="c.paid_by" class="text-[9px] text-[#71717a]">por {{ c.paid_by }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="c.status === 'pending'"
                                        @click="openPayModal(c)"
                                        class="text-[9px] font-bold uppercase tracking-widest text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1.5 rounded transition-colors"
                                    >
                                        Marcar Pagada
                                    </button>
                                    <span v-else class="text-[9px] text-[#3f3f46]">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="commissions.last_page > 1" class="px-6 py-4 border-t border-[#2a2a2a] flex items-center justify-between">
                    <p class="text-[10px] text-[#71717a]">
                        Mostrando {{ commissions.from }}–{{ commissions.to }} de {{ commissions.total }}
                    </p>
                    <div class="flex gap-2">
                        <a
                            v-if="commissions.prev_page_url"
                            :href="commissions.prev_page_url"
                            class="px-3 py-1.5 text-[10px] font-bold text-[#a1a1aa] bg-[#262626] border border-[#3f3f46] rounded hover:bg-[#3f3f46] transition-colors"
                        >Anterior</a>
                        <a
                            v-if="commissions.next_page_url"
                            :href="commissions.next_page_url"
                            class="px-3 py-1.5 text-[10px] font-bold text-[#a1a1aa] bg-[#262626] border border-[#3f3f46] rounded hover:bg-[#3f3f46] transition-colors"
                        >Siguiente</a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
