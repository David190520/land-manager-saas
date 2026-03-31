<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[#121212] pt-6 sm:justify-center sm:pt-0"
    >
        <div class="mb-8 flex flex-col items-center gap-4 animate-fade-in">
            <Link href="/" class="group">
                <div class="w-20 h-20 rounded-2xl bg-[#18181a] border border-[#2a2a2a] flex items-center justify-center shadow-2xl transition-all duration-300 group-hover:border-[#3f3f46] group-hover:bg-[#1c1c1e]">
                    <v-icon name="ri-landscape-line" scale="2.5" fill="#ffffff" />
                </div>
            </Link>
            <div class="text-center">
                <h1 class="text-2xl font-bold text-white tracking-tight">Land Manager</h1>
                <p class="text-sm text-[#71717a] mt-1 font-medium">Gestión Inmobiliaria Profesional</p>
            </div>
        </div>

        <div
            class="mt-2 w-full glass-card px-10 py-10 shadow-2xl sm:max-w-md animate-slide-up border border-[#2a2a2a] relative overflow-hidden"
        >
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#3f3f46] to-transparent"></div>
            <slot />
        </div>
        
        <p class="mt-8 text-[11px] text-[#3f3f46] uppercase tracking-[0.2em] font-bold">
            &copy; 2026 Land Manager SaaS
        </p>
    </div>
</template>
