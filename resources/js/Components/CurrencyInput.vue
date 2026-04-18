<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: [Number, String],
    placeholder: String,
    className: String,
    prefix: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'input']);

// Internal logic to handle the display value with commas
const displayValue = computed({
    get() {
        if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return '';
        
        // Convert to string and strip existing commas
        let val = props.modelValue.toString().replace(/,/g, '');
        
        // Format with thousands separator
        const parts = val.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    },
    set(newValue) {
        // Remove commas before emitting the raw numeric value
        const rawValue = newValue.replace(/,/g, '');
        
        // Check if it's a valid number part (allowing empty or partial decimals)
        if (rawValue === '' || !isNaN(rawValue) || rawValue === '.') {
            emit('update:modelValue', rawValue);
        }
    }
});

const handleInput = (e) => {
    emit('input', e);
};
</script>

<template>
    <div class="relative w-full">
        <div v-if="prefix" class="absolute left-4 top-1/2 -translate-y-1/2 text-[#71717a] text-sm pointer-events-none font-medium">
            {{ prefix }}
        </div>
        <input
            type="text"
            v-model="displayValue"
            class="input-dark bg-[#141414] w-full"
            :class="[prefix ? 'pl-9' : 'px-4', className]"
            :placeholder="placeholder"
            @input="handleInput"
        />
    </div>
</template>
