<script setup>
import { URGENCY_META } from '../../composables/repairStatus';

defineProps({ modelValue: { type: String, default: 'MEDIUM' } });
defineEmits(['update:modelValue']);

const order = ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'];
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <button
            v-for="key in order"
            :key="key"
            type="button"
            @click="$emit('update:modelValue', key)"
            :class="[
                'flex flex-col items-start gap-1 px-3 py-3 rounded-xl border-2 text-left transition-all',
                modelValue === key
                    ? URGENCY_META[key].bg + ' border-transparent shadow-md'
                    : 'border-slate-200 hover:border-slate-300 bg-white',
            ]"
        >
            <div class="flex items-center gap-2">
                <span :class="['w-2.5 h-2.5 rounded-full', URGENCY_META[key].dot]"></span>
                <span class="text-sm font-semibold">{{ URGENCY_META[key].label }}</span>
            </div>
            <span :class="['text-[10px]', modelValue === key ? 'text-white/80' : 'text-slate-500']">
                {{ URGENCY_META[key].sla }}
            </span>
        </button>
    </div>
</template>
