<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    client: Object,
    reservations: Array,
    documents: Array,
    auditLogs: Array,
});

const activeTab = ref('reservations');

// Document upload form
const documentForm = useForm({
    title: '',
    document_type: 'id_submited',
    file: null,
    notes: '',
});

const uploadDocument = () => {
    documentForm.post(route('clients.documents.upload', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            documentForm.reset();
            documentForm.clearErrors();
        },
    });
};

const deleteDocument = (documentId) => {
    if (confirm('¿Eliminar este anexo? La acción es definitiva e irrecuperable.')) {
        router.delete(route('clients.documents.destroy', [props.client.id, documentId]), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <AppLayout>
        <Head :title="client.full_name" />

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-[#71717a] mb-8 animate-fade-in font-medium">
            <Link :href="route('clients.index')" class="hover:text-white transition-colors">Clientes</Link>
            <v-icon name="md-keyboardarrowright" scale="0.8" />
            <span class="text-[#ededed]">{{ client.full_name }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Left Sidebar (Profile summary) -->
            <div class="lg:col-span-1 border-r border-[#2a2a2a] pr-4">
                <div class="sticky top-10">
                    <div class="w-16 h-16 rounded-xl bg-[#262626] border border-[#3f3f46] flex items-center justify-center text-white text-xl font-bold mb-6">
                        {{ client.first_name.charAt(0) }}{{ client.last_name.charAt(0) }}
                    </div>
                    <h1 class="text-xl font-semibold text-white tracking-tight mb-2">{{ client.full_name }}</h1>
                    <div class="space-y-4 text-xs font-medium text-[#71717a]">
                        <p class="flex items-center gap-2">
                            DNI: <span class="text-white">{{ client.document_type }} {{ client.document_number }}</span>
                        </p>
                        <p class="flex justify-between items-center py-2 border-t border-[#2a2a2a]">
                            Oficio:
                            <span class="text-[#a1a1aa]">{{ client.occupation || 'Nro. Designado' }}</span>
                        </p>
                        <p class="flex justify-between items-center py-2 border-t border-[#2a2a2a]">
                            Contacto principal:
                            <span class="text-[#a1a1aa]">{{ client.phone }}</span>
                        </p>
                        <p class="flex justify-between items-center py-2 border-t border-[#2a2a2a]" v-if="client.phone_secondary">
                            Línea Alt:
                            <span class="text-[#a1a1aa]">{{ client.phone_secondary }}</span>
                        </p>
                        <p class="flex justify-between items-center py-2 border-t border-[#2a2a2a]">
                            Email Asignado:
                            <span class="text-white break-all text-right">{{ client.email || '—' }}</span>
                        </p>
                        <p class="flex justify-between items-center py-2 border-t border-[#2a2a2a]">
                            Residencia:
                            <span class="text-right text-[#a1a1aa]">{{ client.address || '—' }}<br>{{ client.city || '—' }}</span>
                        </p>
                    </div>

                    <div v-if="client.notes" class="mt-8 pt-4 border-t border-[#2a2a2a]">
                        <p class="text-[9px] uppercase tracking-widest text-[#71717a] font-bold mb-2">Comentarios / Ficha</p>
                        <p class="text-[11px] text-[#a1a1aa] leading-relaxed italic bg-[#141414] p-3 rounded-lg border border-[#262626]">
                            "{{ client.notes }}"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-3">
                <!-- Navigation Tabs -->
                <div class="flex items-center gap-6 border-b border-[#2a2a2a] mb-8">
                    <button
                        @click="activeTab = 'reservations'"
                        :class="[
                            'pb-3 text-xs font-bold uppercase tracking-widest transition-colors',
                            activeTab === 'reservations' ? 'text-white border-b-2 border-white' : 'text-[#71717a] hover:text-[#a1a1aa]'
                        ]"
                    >
                        Expedientes ({{ reservations.length }})
                    </button>
                    <button
                        @click="activeTab = 'documents'"
                        :class="[
                            'pb-3 text-xs font-bold uppercase tracking-widest transition-colors',
                            activeTab === 'documents' ? 'text-white border-b-2 border-white' : 'text-[#71717a] hover:text-[#a1a1aa]'
                        ]"
                    >
                        Repositorio Base ({{ documents.length }})
                    </button>
                    <button
                        @click="activeTab = 'audit'"
                        :class="[
                            'pb-3 text-xs font-bold uppercase tracking-widest transition-colors',
                            activeTab === 'audit' ? 'text-white border-b-2 border-white' : 'text-[#71717a] hover:text-[#a1a1aa]'
                        ]"
                    >
                        Historial ({{ auditLogs?.length || 0 }})
                    </button>
                </div>

                <!-- Tab: Reservations -->
                <div v-if="activeTab === 'reservations'" class="animate-fade-in space-y-6">
                    <div v-if="reservations.length === 0" class="text-center py-16 bg-[#18181a] border border-[#2a2a2a] rounded-xl text-[#71717a] text-sm">
                        No hay registros inmobiliarios adjudicados a este folio.
                    </div>

                    <div v-for="res in reservations" :key="res.id" class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden shadow">
                        <div class="flex items-center justify-between p-5 bg-[#141414] border-b border-[#2a2a2a]">
                            <div>
                                <h3 class="text-sm font-semibold text-white">Lote-{{ res.lot.lot_number }} {{ res.lot.project.name }}</h3>
                                <p class="text-[10px] text-[#71717a] mt-1">{{ res.lot.block.name }} • {{ res.lot.area }} m²</p>
                            </div>
                            <span :class="[
                                'px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-wider border',
                                res.is_overdue ? 'bg-red-500/20 text-red-500 border-red-500/30 animate-pulse' :
                                res.status === 'pending_approval' ? 'bg-amber-500/20 text-amber-500 border-amber-500/30' :
                                res.status === 'active' || res.status === 'confirmed' ? 'bg-white text-black border-white' :
                                res.status === 'completed' ? 'bg-[#132a13] text-[#4ade80] border-[#4ade80]/30' :
                                'bg-[#0a0a0a] text-[#71717a] border-[#1e1e1e]'
                            ]">
                                {{ res.is_overdue ? 'En Mora' : res.status_label }}
                            </span>
                        </div>
                        <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-0 text-xs">
                            <div class="pr-4 border-r border-[#2a2a2a]">
                                <p class="text-[9px] uppercase tracking-wider text-[#71717a] font-bold mb-1">Enganche</p>
                                <p class="text-white font-medium">{{ formatCurrency(res.down_payment) }}</p>
                            </div>
                            <div class="px-4 border-r-0 md:border-r border-[#2a2a2a]">
                                <p class="text-[9px] uppercase tracking-wider text-[#71717a] font-bold mb-1">Apertura</p>
                                <p class="text-[#a1a1aa]">{{ res.created_at.split('T')[0] }}</p>
                            </div>
                            <div class="px-4 pt-4 md:pt-0 border-r border-[#2a2a2a] mt-4 md:mt-0 border-t md:border-t-0 border-[#2a2a2a]">
                                <p class="text-[9px] uppercase tracking-wider text-[#71717a] font-bold mb-1">Cierre Obligatorio</p>
                                <p class="text-white font-medium">{{ res.payment_deadline }}</p>
                            </div>
                            <div class="pl-4 pt-4 md:pt-0 mt-4 md:mt-0 border-t md:border-t-0 border-[#2a2a2a] text-right">
                                <Link :href="route('lots.show', res.lot.id)" class="text-[10px] uppercase font-bold tracking-widest text-[#71717a] hover:text-white transition-colors">
                                    Inspeccionar
                                </Link>
                            </div>
                        </div>

                        <!-- Payment Plan Snippet -->
                        <div v-if="res.payment_plan" class="bg-[#121212] border-t border-[#2a2a2a] p-4 flex items-center justify-between">
                            <div class="flex items-center gap-4 w-full">
                                <div class="w-24">
                                    <p class="text-[9px] uppercase tracking-wider text-[#71717a] font-bold mb-1">Amortización</p>
                                    <div class="w-full h-1 bg-[#262626] rounded-full overflow-hidden mt-1">
                                        <div class="h-full bg-white transition-all" :style="{ width: `${res.payment_plan.progress}%` }"></div>
                                    </div>
                                </div>
                                <div class="text-[10px]">
                                    <span class="text-[#a1a1aa]">{{ res.payment_plan.paid_installments }}</span>
                                    <span class="text-[#71717a]">/{{ res.payment_plan.total_installments }} CUO</span>
                                </div>
                                <div class="ml-auto text-right">
                                    <Link :href="route('finances.plan', res.payment_plan.id)" class="text-[10px] uppercase font-bold tracking-widest text-white underline hover:opacity-75">
                                        Ir a Cartera
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Documents -->
                <div v-if="activeTab === 'documents'" class="animate-fade-in space-y-6">
                    <!-- Upload Form -->
                    <form @submit.prevent="uploadDocument" class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-6">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-white mb-6 flex items-center gap-2 pb-3 border-b border-[#2a2a2a]">
                            <v-icon name="md-fileupload-outlined" scale="1.1" />
                            Anexar Documento Notarial
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="label-dark text-[10px] uppercase font-bold tracking-wider">Categorización</label>
                                <select v-model="documentForm.document_type" class="input-dark bg-[#121212] h-10 py-0">
                                    <option value="id_submited">Doc. Identidad Oficial</option>
                                    <option value="contract">Pacto Vinculante (Contrato)</option>
                                    <option value="receipt">Comprobante Recaudatorio</option>
                                    <option value="other">Fojas Adicionales / Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="label-dark text-[10px] uppercase font-bold tracking-wider">Identificador Breve</label>
                                <input v-model="documentForm.title" type="text" class="input-dark bg-[#121212] h-10" placeholder="Ej: CI Frontal" />
                                <p v-if="documentForm.errors.title" class="text-red-400 text-[10px] mt-1">{{ documentForm.errors.title }}</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="label-dark text-[10px] uppercase font-bold tracking-wider">Carga Digital (PDF/IMG, max 5MB)</label>
                            <input
                                type="file"
                                @input="documentForm.file = $event.target.files[0]"
                                class="w-full text-xs text-[#a1a1aa] file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border file:border-[#2a2a2a] file:text-xs file:font-semibold file:bg-[#121212] file:text-[#ededed] hover:file:bg-[#1e1e1e]"
                            />
                            <p v-if="documentForm.errors.file" class="text-red-400 text-[10px] mt-1">{{ documentForm.errors.file }}</p>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-[#2a2a2a]">
                            <button type="submit" class="btn-primary" :disabled="documentForm.processing">
                                {{ documentForm.processing ? 'Subiendo...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>

                    <!-- Documents List -->
                    <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-[#2a2a2a]">
                            <h3 class="text-[10px] font-bold text-white tracking-widest uppercase">Índice Archivístico</h3>
                        </div>
                        <table class="table-dark">
                            <thead>
                                <tr>
                                    <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Designación</th>
                                    <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Categoría</th>
                                    <th class="font-bold text-[9px] tracking-widest text-[#71717a]">Alta Digital</th>
                                    <th class="text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="documents.length === 0">
                                    <td colspan="4" class="text-center py-12 text-xs text-[#71717a]">Repositorio vacío.</td>
                                </tr>
                                <tr v-for="doc in documents" :key="doc.id" class="border-[#2a2a2a] hover:bg-[#1e1e1e]">
                                    <td class="font-medium text-white text-xs">
                                        <div class="flex items-center gap-2">
                                            <v-icon name="md-attachfile-outlined" scale="0.8" class="text-[#71717a]"/>
                                            {{ doc.title }}
                                        </div>
                                    </td>
                                    <td class="text-xs text-[#a1a1aa]">{{ doc.document_type_label }}</td>
                                    <td class="text-[10px] text-[#71717a]">{{ doc.created_at.split('T')[0] }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-4">
                                            <a :href="route('clients.documents.download', [client.id, doc.id])" class="text-[#a1a1aa] hover:text-white transition-colors" title="Extraer Original">
                                                <v-icon name="md-download" scale="0.9"/>
                                            </a>
                                            <button @click="deleteDocument(doc.id)" class="text-[#71717a] hover:text-red-400 transition-colors" title="Depurar registro">
                                                <v-icon name="md-deleteoutline" scale="0.9"/>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                </div>

                <!-- Tab: Audit Logs -->
                <div v-if="activeTab === 'audit'" class="animate-fade-in space-y-6">
                    <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl overflow-hidden relative">
                        <!-- timeline line -->
                        <div class="absolute left-10 top-0 bottom-0 w-px bg-[#2a2a2a] z-0 hidden sm:block"></div>
                        
                        <div class="px-6 py-4 border-b border-[#2a2a2a] relative z-10 bg-[#18181a]">
                            <h3 class="text-[10px] font-bold text-white tracking-widest uppercase">Historial de Transacciones (Log)</h3>
                        </div>

                        <div class="p-6 relative z-10">
                            <div v-if="auditLogs.length === 0" class="text-center py-12 text-xs text-[#71717a]">
                                No se ha registrado actividad reciente.
                            </div>
                            
                            <div v-else class="space-y-8">
                                <div v-for="log in auditLogs" :key="log.id" class="flex gap-4 sm:gap-6 relative group">
                                    <!-- Timeline Icon -->
                                    <div class="hidden sm:flex w-8 h-8 rounded-full bg-[#1e1e1e] border-2 border-[#2a2a2a] items-center justify-center flex-shrink-0 z-10 group-hover:border-[#3f3f46] transition-colors relative mt-1">
                                        <v-icon :name="log.action_type === 'created' ? 'md-add' : log.action_type === 'status_changed' ? 'md-warningamber-outlined' : log.action_type === 'payment_recorded' ? 'md-attachmoney-outlined' : 'md-edit-outlined'" scale="0.8" class="text-[#a1a1aa]" />
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                                            <span class="text-[10px] sm:text-xs font-semibold text-[#ededed]">
                                                {{ log.created_at.split(' ')[0] }}
                                            </span>
                                            <span class="text-[#3f3f46]">—</span>
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest bg-[#262626] text-[#a1a1aa]">
                                                {{ log.user_name }}
                                            </span>
                                            <span class="text-[9px] text-[#71717a] ml-auto">
                                                {{ log.created_at.split(' ')[1] }}
                                            </span>
                                        </div>
                                        
                                        <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-4 mt-2">
                                            <p class="text-xs text-white leading-relaxed font-medium">
                                                {{ log.description }}
                                            </p>
                                            
                                            <div v-if="log.old_values && log.new_values && Object.keys(log.new_values).length > 0" class="mt-3 pt-3 border-t border-[#2a2a2a]/50 text-[10px] space-y-1.5">
                                                <div v-for="(newValue, key) in log.new_values" :key="key" class="flex items-start gap-2">
                                                    <span class="text-[#71717a] w-24 flex-shrink-0">{{ key }}:</span>
                                                    <div class="flex flex-wrap items-center gap-2 flex-1 break-all">
                                                        <span class="text-[#ef4444] line-through opacity-80">{{ log.old_values[key] ?? 'nulo' }}</span>
                                                        <v-icon name="md-keyboardarrowright" scale="0.7" class="text-[#71717a] flex-shrink-0" />
                                                        <span class="text-[#4ade80]">{{ newValue ?? 'nulo' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
    </AppLayout>
</template>
