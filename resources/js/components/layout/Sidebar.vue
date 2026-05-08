<script setup>
import { computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import {
    Squares2X2Icon, NewspaperIcon, ChartPieIcon, WrenchScrewdriverIcon,
    BeakerIcon, QrCodeIcon, BellAlertIcon, UsersIcon,
    ArrowRightOnRectangleIcon, DocumentTextIcon, ClockIcon, Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

const auth = useAuthStore();
const router = useRouter();

const groups = computed(() => [
    {
        label: 'เมนูหลัก',
        items: [
            { name: 'หน้าแรก', icon: Squares2X2Icon, to: { name: 'dashboard' } },
        ],
    },
    {
        label: 'จัดการเครื่องมือ',
        items: [
            { name: 'รายการเครื่องมือ', icon: ChartPieIcon, to: { name: 'equipment.list' }, roles: ['admin', 'staff'] },
            { name: 'เพิ่มเครื่องมือ', icon: NewspaperIcon, to: { name: 'equipment.create' }, roles: ['admin', 'staff'] },
            { name: 'สอบเทียบ', icon: BeakerIcon, to: { name: 'calibration.list' }, roles: ['admin', 'staff'] },
        ],
    },
    {
        label: 'งานซ่อมบำรุง',
        items: [
            { name: 'แจ้งซ่อม', icon: WrenchScrewdriverIcon, to: { name: 'repair.create' } },
            { name: 'ประวัติการซ่อม', icon: ClockIcon, to: { name: 'repair.list' } },
        ],
    },
    {
        label: 'อื่นๆ',
        items: [
            { name: 'QR-Code', icon: QrCodeIcon, to: { name: 'qrcode.designer' }, roles: ['admin'] },
            { name: 'MOPH Alert', icon: BellAlertIcon, to: { name: 'moph.settings' }, roles: ['admin'] },
            { name: 'รายงาน', icon: DocumentTextIcon, to: { name: 'reports' }, roles: ['admin', 'staff'] },
        ],
    },
    {
        label: 'จัดการระบบ',
        items: [
            { name: 'ผู้ใช้งาน', icon: UsersIcon, to: { name: 'users.list' }, roles: ['admin'] },
            { name: 'ตั้งค่าระบบ', icon: Cog6ToothIcon, to: { name: 'dashboard' }, roles: ['admin'] },
        ],
    },
]);

const visibleGroups = computed(() =>
    groups.value
        .map((g) => ({
            ...g,
            items: g.items.filter((i) => !i.roles || auth.hasAnyRole(i.roles)),
        }))
        .filter((g) => g.items.length > 0),
);

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <aside class="w-[270px] shrink-0 bg-white border-r border-slate-200 flex flex-col">
        <!-- Logo -->
        <div class="h-[72px] flex items-center gap-3 px-5 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold">
                CK
            </div>
            <div>
                <div class="text-base font-bold text-slate-800 tracking-tight">CK-MEMS</div>
                <div class="text-[10px] text-slate-400 leading-none">Medical Equipment</div>
            </div>
        </div>

        <!-- Menu -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll px-4 py-4 space-y-5">
            <div v-for="group in visibleGroups" :key="group.label">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 px-2 mb-2">
                    {{ group.label }}
                </div>
                <ul class="space-y-0.5">
                    <li v-for="item in group.items" :key="item.name">
                        <RouterLink
                            :to="item.to"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-slate-50 transition-colors"
                            active-class="!bg-blue-50 !text-blue-700 font-medium"
                        >
                            <component :is="item.icon" class="w-5 h-5 shrink-0" />
                            <span>{{ item.name }}</span>
                        </RouterLink>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Logout -->
        <div class="px-4 py-3 border-t border-slate-100">
            <button
                @click="handleLogout"
                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm text-rose-600 hover:bg-rose-50 transition-colors font-medium"
            >
                <ArrowRightOnRectangleIcon class="w-5 h-5" />
                ออกจากระบบ
            </button>
        </div>
    </aside>
</template>
