<script setup>
import { computed } from 'vue';
import { STATUS_META, COLOR_BG } from '../../composables/repairStatus';

const props = defineProps({ status: { type: String, required: true } });

const meta = computed(() => STATUS_META[props.status] ?? STATUS_META.PENDING);
const cls = computed(() => COLOR_BG[meta.value.color] ?? COLOR_BG.slate);
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium', cls]">
        <span>{{ meta.icon }}</span>
        {{ meta.label }}
    </span>
</template>
