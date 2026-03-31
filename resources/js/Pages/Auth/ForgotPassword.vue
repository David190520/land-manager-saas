<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar Contraseña" />

        <div class="mb-6 border-b border-[#2a2a2a] pb-4">
            <h2 class="text-sm font-bold text-white tracking-[0.1em] uppercase">Recuperar Acceso</h2>
        </div>

        <div class="mb-6 text-xs font-medium text-[#71717a] leading-relaxed uppercase tracking-wider">
            ¿Olvidaste tu contraseña? No hay problema. Solo dinos tu dirección de correo electrónico y te enviaremos un enlace para restablecerla.
        </div>

        <div v-if="status" class="mb-4 text-xs font-bold uppercase tracking-wider text-green-400 bg-green-500/10 p-3 rounded-lg border border-green-500/20">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <label class="label-dark uppercase tracking-wider text-[10px] font-bold">Correo Electrónico</label>

                <input
                    id="email"
                    type="email"
                    class="input-dark bg-[#141414]"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="ejemplo@correo.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="pt-4 border-t border-[#2a2a2a] flex items-center justify-between">
                <Link :href="route('login')" class="text-[10px] font-bold text-[#71717a] hover:text-white uppercase tracking-widest transition-colors">
                    Volver al login
                </Link>
                <button
                    class="btn-primary"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Enviar enlace
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
