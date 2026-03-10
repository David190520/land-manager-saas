<script setup>
import Modal from './Modal.vue';

defineProps({
    show: Boolean,
    title: String,
    message: String,
    confirmText: {
        type: String,
        default: 'Confirmar'
    },
    cancelText: {
        type: String,
        default: 'Cancelar'
    },
    type: {
        type: String,
        default: 'info' // danger, success, info
    }
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="show" maxWidth="md" @close="close">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-[#2a2a2a]">
                <div 
                    class="w-10 h-10 rounded-xl flex items-center justify-center border"
                    :class="{
                        'bg-red-500/10 border-red-500/20 text-red-500': type === 'danger',
                        'bg-green-500/10 border-green-500/20 text-green-500': type === 'success',
                        'bg-blue-500/10 border-blue-500/20 text-blue-500': type === 'info',
                    }"
                >
                    <v-icon :name="type === 'danger' ? 'md-warningamber-outlined' : type === 'success' ? 'ri-checkbox-circle-line' : 'ri-information-line'" scale="1.1" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white tracking-tight">{{ title }}</h3>
                    <p class="text-[10px] text-[#71717a] font-bold uppercase tracking-widest leading-none mt-1">Confirmación de Sistema</p>
                </div>
            </div>

            <p class="text-sm text-[#a1a1aa] leading-relaxed mb-8">
                {{ message }}
            </p>

            <div class="flex gap-3">
                <button 
                    @click="close" 
                    class="flex-1 px-4 py-2.5 rounded-xl border border-[#2a2a2a] text-[#71717a] text-xs font-bold uppercase tracking-widest hover:bg-[#1e1e1e] hover:text-white transition-all"
                >
                    {{ cancelText }}
                </button>
                <button 
                    @click="confirm" 
                    class="flex-1 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-lg"
                    :class="{
                        'bg-red-600 hover:bg-red-500 text-white shadow-red-900/20': type === 'danger',
                        'bg-white hover:bg-[#ededed] text-black': type !== 'danger'
                    }"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
