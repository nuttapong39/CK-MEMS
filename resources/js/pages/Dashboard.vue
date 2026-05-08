<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
    FingerPrintIcon, CalendarDaysIcon, BanknotesIcon,
    PencilSquareIcon, BriefcaseIcon, ClockIcon, DocumentTextIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import { useAuthStore } from '../stores/auth';
import { masterApi } from '../api/master';
import { formatThaiShortDate } from '../utils/thaiDate';

const auth = useAuthStore();
const stats = ref(null);
const loading = ref(true);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'สวัสดีตอนเช้า';
    if (h < 17) return 'สวัสดีตอนบ่าย';
    return 'สวัสดีตอนเย็น';
});
const todayLabel = computed(() => formatThaiShortDate(new Date()));

onMounted(async () => {
    try {
        const { data } = await masterApi.dashboardStats();
        stats.value = data;
    } finally {
        loading.value = false;
    }
});

const colorMap = {
    emerald: 'bg-emerald-100 text-emerald-600',
    rose: 'bg-rose-100 text-rose-600',
    slate: 'bg-slate-100 text-slate-600',
    blue: 'bg-blue-100 text-blue-600',
    amber: 'bg-amber-100 text-amber-600',
    violet: 'bg-violet-100 text-violet-600',
};

const shortcuts = [
    { name: 'เพิ่มเครื่องมือ', desc: 'รหัส / รุ่น / ผู้รับผิดชอบ', icon: PencilSquareIcon, accent: 'bg-blue-100 text-blue-600', to: { name: 'equipment.create' } },
    { name: 'รายการเครื่องมือ', desc: 'ค้นหา / กรอง / แก้ไข', icon: BanknotesIcon, accent: 'bg-violet-100 text-violet-600', to: { name: 'equipment.list' } },
    { name: 'แจ้งซ่อม', desc: 'รายงานเครื่องเสีย', icon: BriefcaseIcon, accent: 'bg-rose-100 text-rose-600', to: { name: 'repair.list' } },
    { name: 'สอบเทียบ', desc: 'บันทึกผลสอบเทียบ', icon: FingerPrintIcon, accent: 'bg-emerald-100 text-emerald-600', to: { name: 'dashboard' } },
    { name: 'QR-Code', desc: 'พิมพ์ป้ายเครื่องมือ', icon: CalendarDaysIcon, accent: 'bg-amber-100 text-amber-600', to: { name: 'dashboard' } },
    { name: 'รายงาน', desc: 'PDF / Excel', icon: DocumentTextIcon, accent: 'bg-indigo-100 text-indigo-600', to: { name: 'dashboard' } },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Greeting -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-2xl">👋</div>
                <div>
                    <div class="text-2xl font-bold text-slate-800">
                        {{ greeting }}, <span class="text-blue-600">{{ auth.fullName }}</span>
                    </div>
                    <div class="text-sm text-slate-500 mt-0.5 flex items-center gap-1.5">
                        <BriefcaseIcon class="w-4 h-4" />
                        {{ auth.user?.department?.name_th || 'ไม่ระบุหน่วยงาน' }} · {{ auth.hospitalName }}
                    </div>
                </div>
            </div>
            <div class="card-base p-4 flex items-center gap-3 self-start">
                <CalendarDaysIcon class="w-5 h-5 text-blue-600" />
                <div class="leading-tight">
                    <div class="text-[11px] text-slate-400 uppercase tracking-wider">วันนี้</div>
                    <div class="text-sm font-semibold text-slate-800">{{ todayLabel }}</div>
                </div>
            </div>
        </div>

        <!-- Hero Card -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 rounded-2xl p-6 lg:p-8 shadow-xl shadow-blue-500/20 text-white relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,_white_0%,_transparent_50%)]"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 text-blue-100 text-sm font-medium">
                        <ChartBarIcon class="w-4 h-4" />
                        {{ stats?.hero?.label || 'เครื่องมือทั้งหมด' }}
                    </div>
                    <div class="text-5xl font-bold mt-2 tracking-tight">
                        {{ loading ? '—' : (stats?.hero?.value ?? 0) }}
                        <span class="text-lg font-normal text-blue-100">{{ stats?.hero?.unit || 'รายการ' }}</span>
                    </div>
                </div>
                <div class="hidden md:block w-20 h-20 rounded-2xl bg-white/10 backdrop-blur flex items-center justify-center">
                    <span class="text-4xl">🩺</span>
                </div>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="card in stats?.cards || []"
                :key="card.key"
                class="card-base p-5 flex items-center gap-4"
            >
                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', colorMap[card.color] ?? colorMap.slate]">
                    <ChartBarIcon class="w-6 h-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-slate-500 truncate">{{ card.label }}</div>
                    <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ card.value }}</div>
                </div>
            </div>
        </div>

        <!-- Shortcuts -->
        <div>
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                เมนูลัด (QUICK SHORTCUTS)
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <RouterLink
                    v-for="s in shortcuts"
                    :key="s.name"
                    :to="s.to"
                    class="card-base p-5 flex items-start gap-4 hover:shadow-md hover:-translate-y-0.5 transition cursor-pointer"
                >
                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center shrink-0', s.accent]">
                        <component :is="s.icon" class="w-6 h-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-800">{{ s.name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ s.desc }}</div>
                    </div>
                </RouterLink>
            </div>
        </div>
    </div>
</template>
