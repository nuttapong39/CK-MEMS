<script setup>
import { computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore }     from '../../stores/auth';
import { useSettingsStore } from '../../stores/settings';
import {
    Squares2X2Icon, NewspaperIcon, ChartPieIcon, WrenchScrewdriverIcon,
    BeakerIcon, QrCodeIcon, BellAlertIcon, UsersIcon,
    ArrowRightOnRectangleIcon, DocumentTextIcon, ClockIcon, Cog6ToothIcon,
    TruckIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    open: { type: Boolean, default: true },
});
const emit = defineEmits(['close']);

const auth        = useAuthStore();
const appSettings = useSettingsStore();
const router      = useRouter();

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
            { name: 'รายการเครื่องมือ', icon: ChartPieIcon,           to: { name: 'equipment.list' },   roles: ['admin', 'staff'] },
            { name: 'เพิ่มเครื่องมือ',  icon: NewspaperIcon,           to: { name: 'equipment.create' }, roles: ['admin', 'staff'] },
            { name: 'สอบเทียบ',          icon: BeakerIcon,              to: { name: 'calibration.list' }, roles: ['admin', 'staff'] },
        ],
    },
    {
        label: 'งานซ่อมบำรุง',
        items: [
            { name: 'แจ้งซ่อม',       icon: WrenchScrewdriverIcon, to: { name: 'repair.create' } },
            { name: 'ประวัติการซ่อม', icon: ClockIcon,             to: { name: 'repair.list' } },
            { name: 'ส่งซ่อมภายนอก', icon: TruckIcon,             to: { name: 'repair.outsourced' }, roles: ['admin', 'staff'] },
        ],
    },
    {
        label: 'อื่นๆ',
        items: [
            { name: 'QR-Code',    icon: QrCodeIcon,      to: { name: 'qrcode.designer' }, roles: ['admin'] },
            { name: 'MOPH Alert', icon: BellAlertIcon,   to: { name: 'moph.settings' },   roles: ['admin'] },
            { name: 'รายงาน',    icon: DocumentTextIcon, to: { name: 'reports' },         roles: ['admin', 'staff'] },
        ],
    },
    {
        label: 'จัดการระบบ',
        items: [
            { name: 'ผู้ใช้งาน',   icon: UsersIcon,     to: { name: 'users.list' },      roles: ['admin'] },
            { name: 'ตั้งค่าระบบ', icon: Cog6ToothIcon, to: { name: 'system.settings' }, roles: ['admin'] },
        ],
    },
]);

const visibleGroups = computed(() =>
    groups.value
        .map((g) => ({ ...g, items: g.items.filter((i) => !i.roles || auth.hasAnyRole(i.roles)) }))
        .filter((g) => g.items.length > 0),
);

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}

function handleNavClick() {
    // Close sidebar on mobile after navigation
    if (window.innerWidth < 1024) {
        emit('close');
    }
}
</script>

<template>
    <aside :class="['sidebar th-sidebar flex flex-col border-r', { open }]">

        <!-- Logo / Brand -->
        <div class="h-[72px] flex items-center justify-between gap-3 px-4 border-b th-divider shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="th-sidebar-logo w-10 h-10 rounded-xl overflow-hidden shrink-0 flex items-center justify-center">
                    <img
                        v-if="appSettings.logoUrl"
                        :src="appSettings.logoUrl"
                        :key="appSettings.logoUrl"
                        alt="logo"
                        class="w-full h-full object-contain"
                    />
                    <span v-else class="text-white font-bold text-sm">CK</span>
                </div>
                <div class="min-w-0 sidebar-text-fade">
                    <div class="th-sidebar-name text-sm font-bold tracking-tight truncate">
                        {{ appSettings.systemName }}
                    </div>
                    <div class="th-sidebar-sub-text text-[10px] leading-none truncate">Medical Equipment</div>
                </div>
            </div>
            <!-- Close button (mobile only) -->
            <button
                @click="emit('close')"
                class="th-sidebar-label p-1.5 rounded-lg lg:hidden shrink-0 hover:opacity-70 transition"
            >
                <XMarkIcon class="w-5 h-5" />
            </button>
        </div>

        <!-- Nav Menu -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll px-3 py-4 space-y-4">
            <div v-for="group in visibleGroups" :key="group.label">
                <div class="th-sidebar-label text-[10px] font-semibold uppercase tracking-widest px-2 mb-1.5 sidebar-text-fade">
                    {{ group.label }}
                </div>
                <ul class="space-y-0.5">
                    <li v-for="item in group.items" :key="item.name">
                        <RouterLink
                            :to="item.to"
                            @click="handleNavClick"
                            class="th-nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors"
                        >
                            <component :is="item.icon" class="w-5 h-5 shrink-0" />
                            <span class="sidebar-text-fade truncate">{{ item.name }}</span>
                        </RouterLink>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Logout -->
        <div class="px-3 py-3 border-t th-divider shrink-0">
            <button
                @click="handleLogout"
                class="th-logout flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
            >
                <ArrowRightOnRectangleIcon class="w-5 h-5 shrink-0" />
                <span class="sidebar-text-fade">ออกจากระบบ</span>
            </button>
        </div>
    </aside>
</template>

<style scoped>
/* ── Mobile: fixed overlay drawer ───────────────────────────────── */
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 260px;
    z-index: 40;
    transform: translateX(-100%);
    transition: transform 0.3s ease, width 0.3s ease;
    overflow: hidden;
}

.sidebar.open {
    transform: translateX(0);
}

/* ── Desktop: inline flex column ────────────────────────────────── */
@media (min-width: 1024px) {
    .sidebar {
        position: relative;
        top: auto;
        bottom: auto;
        left: auto;
        z-index: auto;
        transform: translateX(0) !important;
        width: 260px;
        overflow: visible;
    }

    .sidebar:not(.open) {
        width: 0;
        overflow: hidden;
        border-right: none;
    }
}

/* Fade out text labels when collapsing */
.sidebar:not(.open) .sidebar-text-fade {
    opacity: 0;
    pointer-events: none;
}
</style>
