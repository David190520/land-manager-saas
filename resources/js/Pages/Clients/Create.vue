<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
    form.post(route('clients.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="Nuevo Cliente" />

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-[#71717a] mb-8 animate-fade-in font-medium">
            <Link :href="route('clients.index')" class="hover:text-white transition-colors">Padrón</Link>
            <v-icon name="md-keyboardarrowright" scale="0.8" />
            <span class="text-[#ededed]">Inscripción de Titular</span>
        </div>

        <div class="max-w-3xl">
            <h1 class="text-2xl font-semibold text-white tracking-tight mb-8 animate-fade-in">Ficha Técnica</h1>

            <form @submit.prevent="submit" class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-8 space-y-10 animate-slide-up">
                <!-- Personal Info -->
                <div>
                    <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a]">Identidad Judicial</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="label-dark">Pre-Nombres *</label>
                            <input v-model="form.first_name" type="text" class="input-dark bg-[#121212]" placeholder="EJ: Juan" />
                            <p v-if="form.errors.first_name" class="text-red-400 text-[10px] mt-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="label-dark">Apellidos Familiares *</label>
                            <input v-model="form.last_name" type="text" class="input-dark bg-[#121212]" placeholder="EJ: Perez" />
                            <p v-if="form.errors.last_name" class="text-red-400 text-[10px] mt-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="label-dark">Categoría DNI *</label>
                            <select v-model="form.document_type" class="input-dark bg-[#121212]">
                                <option value="CC">Cédula Civ.</option>
                                <option value="CE">Cédula Ext.</option>
                                <option value="NIT">Matrícula (NIT)</option>
                                <option value="Pasaporte">Pasaporte Int.</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-dark">Folio/Número *</label>
                            <input v-model="form.document_number" type="text" class="input-dark bg-[#121212]" placeholder="Nro de Identificación" />
                            <p v-if="form.errors.document_number" class="text-red-400 text-[10px] mt-1">{{ form.errors.document_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a]">Medios de Aviso</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="label-dark">Nro. Primario *</label>
                            <input v-model="form.phone" type="text" class="input-dark bg-[#121212]" placeholder="+57 XXX XXX XXXX" />
                            <p v-if="form.errors.phone" class="text-red-400 text-[10px] mt-1">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="label-dark">Alternativo</label>
                            <input v-model="form.phone_secondary" type="text" class="input-dark bg-[#121212]" placeholder="Opcional" />
                        </div>
                    </div>
                    <div class="mt-6">
                        <label class="label-dark">Casilla Digital (Email)</label>
                        <input v-model="form.email" type="email" class="input-dark bg-[#121212]" placeholder="titular@dominio.com" />
                        <p v-if="form.errors.email" class="text-red-400 text-[10px] mt-1">{{ form.errors.email }}</p>
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a]">Residencia</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="label-dark">Nomenclatura Postal</label>
                            <input v-model="form.address" type="text" class="input-dark bg-[#121212]" placeholder="Calle / Cra" />
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="label-dark">Cabecera</label>
                                <input v-model="form.city" type="text" class="input-dark bg-[#121212]" placeholder="Municipio" />
                            </div>
                            <div>
                                <label class="label-dark">Provincia / Depto</label>
                                <input v-model="form.department" type="text" class="input-dark bg-[#121212]" placeholder="Región" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional -->
                <div>
                    <h3 class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-6 pb-2 border-b border-[#2a2a2a]">Perfil</h3>
                    <div>
                        <label class="label-dark">Gremio / Ejercicio</label>
                        <input v-model="form.occupation" type="text" class="input-dark bg-[#121212]" placeholder="Naturaleza de Trabajo" />
                    </div>
                    <div class="mt-6">
                        <label class="label-dark">Observaciones Internas</label>
                        <textarea v-model="form.notes" class="input-dark bg-[#121212]" rows="2" placeholder="Notas..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-6 mt-10">
                    <Link :href="route('clients.index')" class="btn-secondary py-3 px-8 uppercase tracking-widest text-[10px]">Abandonar</Link>
                    <button type="submit" class="btn-primary py-3 px-8 uppercase tracking-widest text-[10px]" :disabled="form.processing">
                        {{ form.processing ? 'Completando...' : 'Asentar Registro' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
