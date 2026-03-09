<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    first_name: '',
    last_name: '',
    document_type: 'CC',
    document_number: '',
    email: '',
    phone: '',
    phone_secondary: '',
    address: '',
    city: '',
    department: '',
    occupation: '',
    notes: '',
});

const submit = () => {
    form.post(route('clients.store'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
        preserveScroll: true,
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" maxWidth="3xl" @close="closeModal">
        <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-8 relative overflow-hidden">
            <!-- Decorative corner -->
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-white/5 blur-2xl rounded-full"></div>

            <div class="flex items-center justify-between mb-8 pb-4 border-b border-[#2a2a2a]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#262626] border border-[#3f3f46] flex items-center justify-center">
                        <v-icon name="md-personadd-outlined" scale="1.1" fill="white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-white tracking-tight">Ficha Técnica</h2>
                        <p class="text-[10px] text-[#71717a] font-bold uppercase tracking-widest">Inscripción de Titular</p>
                    </div>
                </div>
                <button @click="closeModal" class="p-2 text-[#71717a] hover:text-white transition-colors">
                    <v-icon name="md-close" scale="1.2" />
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Personal Info -->
                <div>
                    <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a] flex items-center gap-2">
                        <v-icon name="md-badge-outlined" scale="0.8" />
                        Identidad Judicial
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="label-dark">Pre-Nombres *</label>
                            <input v-model="form.first_name" type="text" class="input-dark bg-[#121212] border-[#2a2a2a] focus:border-white focus:ring-0" placeholder="EJ: Juan" />
                            <p v-if="form.errors.first_name" class="text-red-400 text-[10px] mt-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="label-dark">Apellidos Familiares *</label>
                            <input v-model="form.last_name" type="text" class="input-dark bg-[#121212] border-[#2a2a2a] focus:border-white focus:ring-0" placeholder="EJ: Perez" />
                            <p v-if="form.errors.last_name" class="text-red-400 text-[10px] mt-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="label-dark">Categoría DNI *</label>
                            <select v-model="form.document_type" class="input-dark bg-[#121212] border-[#2a2a2a] focus:border-white focus:ring-0">
                                <option value="CC">Cédula Civ.</option>
                                <option value="CE">Cédula Ext.</option>
                                <option value="NIT">Matrícula (NIT)</option>
                                <option value="Pasaporte">Pasaporte Int.</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-dark">Folio/Número *</label>
                            <input v-model="form.document_number" type="text" class="input-dark bg-[#121212] border-[#2a2a2a] focus:border-white focus:ring-0" placeholder="Nro de Identificación" />
                            <p v-if="form.errors.document_number" class="text-red-400 text-[10px] mt-1">{{ form.errors.document_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contact & Address -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a] flex items-center gap-2">
                            <v-icon name="md-contactphone-outlined" scale="0.8" />
                            Medios de Aviso
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="label-dark">Nro. Primario *</label>
                                <input v-model="form.phone" type="text" class="input-dark bg-[#121212] border-[#2a2a2a]" placeholder="+57 XXX XXX XXXX" />
                                <p v-if="form.errors.phone" class="text-red-400 text-[10px] mt-1">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="label-dark">Casilla Digital (Email)</label>
                                <input v-model="form.email" type="email" class="input-dark bg-[#121212] border-[#2a2a2a]" placeholder="titular@dominio.com" />
                                <p v-if="form.errors.email" class="text-red-400 text-[10px] mt-1">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a] flex items-center gap-2">
                            <v-icon name="md-homework-outlined" scale="0.8" />
                            Residencia
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="label-dark">Ciudad / Depto</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input v-model="form.city" type="text" class="input-dark bg-[#121212] border-[#2a2a2a]" placeholder="Municipio" />
                                    <input v-model="form.department" type="text" class="input-dark bg-[#121212] border-[#2a2a2a]" placeholder="Depto" />
                                </div>
                            </div>
                            <div>
                                <label class="label-dark">Gremio / Ejercicio</label>
                                <input v-model="form.occupation" type="text" class="input-dark bg-[#121212] border-[#2a2a2a]" placeholder="Naturaleza de Trabajo" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-[#2a2a2a]">
                    <button type="button" @click="closeModal" class="btn-secondary py-2.5 px-6 uppercase tracking-widest text-[9px] font-bold">Cancelar</button>
                    <button type="submit" class="btn-primary py-2.5 px-6 uppercase tracking-widest text-[9px] font-bold shadow-lg shadow-white/5" :disabled="form.processing">
                        {{ form.processing ? 'Registrando...' : 'Asentar Registro' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
