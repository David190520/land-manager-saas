<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Acceso al Sistema" />

        <div class="mb-8 border-b border-[#2a2a2a] pb-6">
            <h2 class="text-lg font-semibold text-white tracking-tight uppercase text-center">Bienvenido de nuevo</h2>
            <p class="text-[11px] text-[#71717a] text-center mt-2 uppercase tracking-[0.1em] font-bold">Inicia sesión para continuar</p>
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
                    class="input-dark bg-[#141414] focus:bg-[#18181a]"
                    v-model="form.email"
                    placeholder="ejemplo@correo.com"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="label-dark uppercase tracking-wider text-[10px] font-bold mb-0">Contraseña</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[10px] font-bold text-[#71717a] hover:text-white uppercase tracking-wider transition-colors"
                    >
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <input
                    id="password"
                    type="password"
                    class="input-dark bg-[#141414] focus:bg-[#18181a]"
                    v-model="form.password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center group cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="bg-[#121212] border-[#2a2a2a] text-[#ededed] focus:ring-0 focus:ring-offset-0 rounded" />
                    <span class="ms-2 text-[10px] font-bold text-[#71717a] group-hover:text-[#a1a1aa] uppercase tracking-wider transition-colors"
                        >Recordarme</span
                    >
                </label>
            </div>

            <div class="pt-4 border-t border-[#2a2a2a]">
                <button
                    class="btn-primary w-full shadow-lg"
                    :class="{ 'opacity-25 shadow-none': form.processing }"
                    :disabled="form.processing"
                >
                    <v-icon v-if="!form.processing" name="md-login" scale="0.9" class="mr-2" />
                    {{ form.processing ? 'Accediendo...' : 'Ingresar al Portal' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
