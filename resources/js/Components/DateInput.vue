<script setup>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Date, null],
        default: null
    },
    placeholder: {
        type: String,
        default: 'Seleccionar fecha'
    }
});

const emit = defineEmits(['update:modelValue']);

const value = computed({
    get: () => props.modelValue,
    set: (val) => {
        emit('update:modelValue', val);
    }
});

const format = (date) => {
    if (!date) return '';
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${year}-${month}-${day}`;
};
</script>

<template>
    <VueDatePicker 
        v-model="value" 
        :enable-time-picker="false" 
        :auto-apply="true" 
        :format="format" 
        model-type="yyyy-MM-dd"
        :placeholder="placeholder"
        theme="dark"
        class="custom-datepicker"
        :dark="true"
        teleport="body"
    />
</template>

<style>
.custom-datepicker {
    --dp-background-color: #121212;
    --dp-text-color: #ededed;
    --dp-hover-color: #1e1e1e;
    --dp-hover-text-color: #ffffff;
    --dp-hover-icon-color: #ffffff;
    --dp-primary-color: #262626;
    --dp-primary-text-color: #ffffff;
    --dp-secondary-color: #2a2a2a;
    --dp-border-color: #3f3f46;
    --dp-menu-border-color: #2a2a2a;
    --dp-border-color-hover: #a1a1aa;
    --dp-disabled-color: #0a0a0a;
    --dp-scroll-bar-background: #18181a;
    --dp-scroll-bar-color: #3f3f46;
    --dp-success-color: #4ade80;
    --dp-icon-color: #71717a;
    --dp-danger-color: #ef4444;
    --dp-menu-z-index: 9999;
    width: 150px;
    min-width: 150px;
}

.custom-datepicker .dp__input {
    font-size: 11px;
    height: 32px;
    padding-top: 0;
    padding-bottom: 0;
    font-family: inherit;
    background-color: #121212;
    border-color: #3f3f46;
    color: white;
}

.custom-datepicker .dp__input:hover {
    border-color: #a1a1aa;
}

.custom-datepicker .dp__input_focus {
    border-color: white;
}
</style>
