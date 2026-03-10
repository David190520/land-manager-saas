<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success'); // success, error, info

const hideNotification = () => {
    show.value = false;
};

const triggerNotification = (msg, t = 'success') => {
    message.value = msg;
    type.value = t;
    show.value = true;
    
    setTimeout(() => {
        hideNotification();
    }, 5000);
};

// Watch for flash messages from Inertia
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        triggerNotification(flash.success, 'success');
    }
    if (flash?.error) {
        triggerNotification(flash.error, 'error');
    }
}, { deep: true });

onMounted(() => {
    if (page.props.flash?.success) {
        triggerNotification(page.props.flash.success, 'success');
    }
    if (page.props.flash?.error) {
        triggerNotification(page.props.flash.error, 'error');
    }
});

defineExpose({ triggerNotification });
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed top-6 right-6 z-[100] max-w-sm w-full animate-fade-in">
            <div class="bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-4 shadow-2xl relative overflow-hidden group">
                <!-- Progress bar decoration -->
                <div 
                    class="absolute bottom-0 left-0 h-[2px] bg-white transition-all duration-[5000ms] ease-linear"
                    :class="{ 'w-full': show, 'w-0': !show }"
                ></div>

                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 pt-1">
                        <div v-if="type === 'success'" class="w-8 h-8 rounded-lg bg-green-500/10 border border-green-500/20 flex items-center justify-center">
                            <v-icon name="ri-checkbox-circle-line" class="text-green-500" scale="1.1" />
                        </div>
                        <div v-else-if="type === 'error'" class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                            <v-icon name="ri-error-warning-line" class="text-red-500" scale="1.1" />
                        </div>
                        <div v-else class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <v-icon name="ri-information-line" class="text-blue-500" scale="1.1" />
                        </div>
                    </div>
                    
                    <div class="flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#71717a] mb-1">
                            {{ type === 'success' ? 'Notificación' : type === 'error' ? 'Alerta de Sistema' : 'Aviso' }}
                        </p>
                        <p class="text-xs text-[#ededed] leading-relaxed font-medium">
                            {{ message }}
                        </p>
                    </div>

                    <button @click="hideNotification" class="text-[#71717a] hover:text-white transition-colors">
                        <v-icon name="md-close" scale="0.9" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
