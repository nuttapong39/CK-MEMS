<script setup>
import { computed, ref } from 'vue';
import BaseModal from '../common/BaseModal.vue';
import { MagnifyingGlassIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { useMasterStore } from '../../stores/master';

const props = defineProps({
    open: { type: Boolean, default: false },
    selected: { type: Object, default: null },
});
const emit = defineEmits(['close', 'select']);

const master = useMasterStore();
const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return master.equipmentCodes;
    return master.equipmentCodes.filter((c) =>
        c.code.toLowerCase().includes(term)
        || c.name_th?.toLowerCase().includes(term)
        || c.name_en?.toLowerCase().includes(term),
    );
});

function pick(code) {
    emit('select', code);
    emit('close');
}

const riskColor = (code) => ({
    HIGH: 'bg-rose-100 text-rose-700',
    MEDIUM: 'bg-amber-100 text-amber-700',
    LOW: 'bg-blue-100 text-blue-700',
    MINIMAL: 'bg-slate-100 text-slate-600',
}[code] ?? 'bg-slate-100 text-slate-600');
</script>

<template>
    <BaseModal
        :open="open"
        @close="emit('close')"
        title="เลือกรหัสครุภัณฑ์ทางการแพทย์"
        subtitle="ค้นหาด้วยรหัส / ชื่อภาษาไทย / ชื่อภาษาอังกฤษ"
        size="3xl"
    >
        <!-- Search -->
        <div class="relative mb-4">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
            <input
                v-model="search"
                type="text"
                autofocus
                placeholder="พิมพ์รหัส (เช่น DEF, INP) หรือชื่อเครื่อง"
                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
            />
        </div>

        <div v-if="!master.equipmentCodes.length" class="py-12 text-center text-sm text-slate-400">
            กำลังโหลดข้อมูลรหัสเครื่องมือ...
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
                v-for="code in filtered"
                :key="code.id"
                @click="pick(code)"
                :class="[
                    'flex items-start gap-3 p-3 rounded-xl border text-left transition-all',
                    selected?.id === code.id
                        ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100'
                        : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50',
                ]"
            >
                <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center font-bold text-blue-700 text-xs">
                    {{ code.code }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-sm text-slate-800 truncate">{{ code.name_th }}</div>
                    <div v-if="code.name_en" class="text-xs text-slate-500 truncate">{{ code.name_en }}</div>
                    <div v-if="code.risk_level" class="mt-1.5 inline-flex">
                        <span :class="['text-[10px] px-2 py-0.5 rounded-full font-medium', riskColor(code.risk_level.code)]">
                            {{ code.risk_level.name_th }}
                        </span>
                    </div>
                </div>
                <CheckCircleIcon
                    v-if="selected?.id === code.id"
                    class="w-5 h-5 text-blue-600 shrink-0"
                />
            </button>
        </div>

        <div v-if="master.equipmentCodes.length && !filtered.length" class="py-12 text-center text-sm text-slate-400">
            ไม่พบรหัสที่ตรงกับ "{{ search }}"
        </div>

        <template #footer>
            <button
                @click="emit('close')"
                class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition"
            >
                ยกเลิก
            </button>
        </template>
    </BaseModal>
</template>
