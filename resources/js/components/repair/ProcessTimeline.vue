<script setup>
import { computed } from 'vue';
import { STATUS_META, COLOR_BG, STATUS_PIPELINE } from '../../composables/repairStatus';

const props = defineProps({
    currentStatus: { type: String, required: true },
    logs: { type: Array, default: () => [] },
});

const isCancelled = computed(() => props.currentStatus === 'CANCELLED');

const reachedSet = computed(() => {
    const set = new Set(props.logs.map((l) => l.to_status));
    set.add(props.currentStatus);
    return set;
});

function dotClass(status) {
    if (isCancelled.value) return 'bg-gray-300 text-gray-400 border-gray-200';
    if (props.currentStatus === status) {
        const meta = STATUS_META[status];
        return (COLOR_BG[meta.color] ?? COLOR_BG.slate) + ' ring-4 ring-white border-2 border-white';
    }
    if (reachedSet.value.has(status)) {
        return 'bg-emerald-500 text-white border-2 border-white';
    }
    return 'bg-slate-100 text-slate-400 border-2 border-white';
}

function lineClass(idx) {
    if (isCancelled.value) return 'bg-gray-200';
    const after = STATUS_PIPELINE[idx + 1];
    if (after && reachedSet.value.has(after)) return 'bg-emerald-400';
    return 'bg-slate-200';
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="isCancelled" class="text-center py-3 px-4 rounded-xl bg-gray-100 text-gray-500 text-sm">
            ✖ การซ่อมนี้ถูกยกเลิกแล้ว
        </div>

        <div v-else class="flex items-center justify-between relative">
            <template v-for="(s, idx) in STATUS_PIPELINE" :key="s">
                <div class="flex flex-col items-center gap-2 z-10 flex-1 min-w-0">
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-base shadow', dotClass(s)]">
                        {{ STATUS_META[s].icon }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-center text-slate-600 font-medium leading-tight px-1">
                        {{ STATUS_META[s].label }}
                    </div>
                </div>
                <div
                    v-if="idx < STATUS_PIPELINE.length - 1"
                    :class="['h-0.5 flex-1 -mx-1', lineClass(idx)]"
                ></div>
            </template>
        </div>

        <!-- Detailed log -->
        <div v-if="logs.length" class="border-t border-slate-100 pt-4 mt-4 space-y-2">
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold">ประวัติการเปลี่ยนสถานะ</div>
            <ol class="space-y-2.5 relative pl-5 border-l-2 border-slate-100">
                <li
                    v-for="(log, i) in logs"
                    :key="log.id || i"
                    class="relative"
                >
                    <span class="absolute -left-[26px] top-1.5 w-3 h-3 rounded-full bg-blue-500 ring-4 ring-blue-50"></span>
                    <div class="text-sm text-slate-700">
                        <span v-if="log.from_status" class="text-slate-400">{{ STATUS_META[log.from_status]?.label || log.from_status }}</span>
                        <span v-else class="text-slate-400">เริ่มต้น</span>
                        <span class="mx-1.5 text-slate-300">→</span>
                        <span class="font-medium">{{ STATUS_META[log.to_status]?.label || log.to_status }}</span>
                    </div>
                    <div v-if="log.note" class="text-xs text-slate-500 mt-0.5">{{ log.note }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        {{ log.changed_by }} · {{ new Date(log.changed_at).toLocaleString('th-TH') }}
                    </div>
                </li>
            </ol>
        </div>
    </div>
</template>
