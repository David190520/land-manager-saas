<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
});

const form = useForm({
    name: props.tenant?.name || '',
    company_name: props.tenant?.company_name || '',
    nit: props.tenant?.nit || '',
    phone: props.tenant?.phone || '',
    email: props.tenant?.email || '',
    address: props.tenant?.address || '',
    city: props.tenant?.city || '',
    department: props.tenant?.department || '',
});
</script>

<template>
    <AppLayout>
        <Head title="Parámetros" />

        <div class="mb-8 animate-fade-in">
            <h1 class="text-2xl font-semibold text-white tracking-tight">Parámetros Maestros</h1>
            <p class="text-xs text-[#71717a] mt-1 font-medium tracking-wide">Configuración estructural del inquilino u organización.</p>
        </div>

        <div class="max-w-3xl space-y-8 lg:space-y-12">
            <!-- Company Info -->
            <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors">
                <h3 class="text-[10px] font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-3 pb-3 border-b border-[#2a2a2a]">
                    <v-icon name="md-workoutline" scale="1.1" fill="#71717a" />
                    Identidad Corporativa
                </h3>

                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Razón Social</label>
                            <input v-model="form.company_name" type="text" class="input-dark bg-[#121212]" readonly />
                        </div>
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">NIT / RUT</label>
                            <input v-model="form.nit" type="text" class="input-dark bg-[#121212]" readonly />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Línea Contacto</label>
                            <input v-model="form.phone" type="text" class="input-dark bg-[#121212]" readonly />
                        </div>
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Canal Web</label>
                            <input v-model="form.email" type="email" class="input-dark bg-[#121212]" readonly />
                        </div>
                    </div>
                    <div>
                        <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Matriz Principal (Dirección)</label>
                        <input v-model="form.address" type="text" class="input-dark bg-[#121212]" readonly />
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Distrito</label>
                            <input v-model="form.city" type="text" class="input-dark bg-[#121212]" readonly />
                        </div>
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Región Territorial</label>
                            <input v-model="form.department" type="text" class="input-dark bg-[#121212]" readonly />
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors" style="animation-delay: 100ms">
                <h3 class="text-[10px] font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-3 pb-3 border-b border-[#2a2a2a]">
                    <v-icon name="md-settings-outlined" scale="1.1" fill="#71717a" />
                    Entorno Analítico
                </h3>
                <div class="space-y-4 text-xs font-semibold uppercase tracking-wider text-white">
                    <div class="flex justify-between py-3 border-b border-[#2a2a2a]">
                        <span class="text-[#71717a]">Licencia</span>
                        <span class="px-2 py-1 rounded border border-[#3f3f46] bg-[#262626] text-white tracking-widest">
                            Enterprise (Ilimitada)
                        </span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-[#2a2a2a]">
                        <span class="text-[#71717a]">Topología Crédito</span>
                        <span class="text-[#ededed]">Amortización Lineal Tasa Fija (0%)</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-[#2a2a2a]">
                        <span class="text-[#71717a]">Depuración Padrón</span>
                        <span class="text-[#a1a1aa]">Expulsión Automática en Mora > 10d</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="text-[#71717a]">Core App</span>
                        <span class="text-[#ededed] font-medium tracking-normal text-sm">v1.2.0-rc</span>
                    </div>
                </div>
            </div>

            <!-- Roles -->
            <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl animate-slide-up hover:border-[#3f3f46] transition-colors" style="animation-delay: 200ms">
                <h3 class="text-[10px] font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-3 pb-3 border-b border-[#2a2a2a]">
                     <v-icon name="md-peopleoutline" scale="1.1" fill="#71717a" />
                     Jerarquía Acceso (ACL)
                </h3>
                <div class="space-y-4">
                    <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-5 hover:border-[#3f3f46] transition-colors">
                        <div class="flex items-center justify-between xl:justify-start xl:gap-6 mb-3">
                            <p class="text-white font-bold text-sm tracking-wide">SYSADMIN / FINANCIERO</p>
                            <span class="px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest border border-white text-black bg-white">
                                Nivel 1 (Total)
                            </span>
                        </div>
                        <p class="text-[11px] font-medium text-[#a1a1aa] leading-relaxed">Auditoría irrestricta. Aprobación de ventas directas. Ejecución de asientos crediticios, emisión de certificaciones notariadas y reajuste de parámetros.</p>
                    </div>
                    <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-5 hover:border-[#3f3f46] transition-colors">
                        <div class="flex items-center justify-between xl:justify-start xl:gap-6 mb-3">
                            <p class="text-white font-bold text-sm tracking-wide">EJECUTIVO COMERCIAL</p>
                            <span class="px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest border border-[#3f3f46] text-[#ededed] bg-[#262626]">
                                Nivel 2 (Segmentado)
                            </span>
                        </div>
                        <p class="text-[11px] font-medium text-[#71717a] leading-relaxed">Exclusión forzosa a módulos contables y estructurales. Capacitado para separar lotificaciones, cargar pre-contratos y tramitar filiaciones de base civil en padrón.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
