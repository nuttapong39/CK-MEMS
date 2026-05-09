<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { BellIcon, UserCircleIcon, ShieldCheckIcon, Bars3Icon } from '@heroicons/vue/24/outline';
import { useAuthStore }     from '../../stores/auth';
import { formatThaiFullDate, formatThaiTime } from '../../utils/thaiDate';

const emit = defineEmits(['toggle']);
const auth = useAuthStore();
const now  = ref(new Date());

let timerId;
onMounted(() => { timerId = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => clearInterval(timerId));

const dateText  = computed(() => formatThaiFullDate(now.value));
const timeText  = computed(() => `เวลา ${formatThaiTime(now.value)}`);

const roleLabel = computed(() => {
    if (auth.isAdmin) return 'ADMIN';
    if (auth.isStaff) return 'STAFF';
    return 'USER';
});
</script>

<template>
    <header class="th-topbar h-[72px] border-b px-4 lg:px-6 flex items-center justify-between gap-4 shrink-0">

        <!-- Left: hamburger + date -->
        <div class="flex items-center gap-3">
            <!-- Sidebar toggle button -->
            <button
                @click="emit('toggle')"
                class="th-topbar-icon th-topbar-icon-btn p-2 rounded-xl transition-colors"
                title="เปิด/ปิด Sidebar"
            >
                <Bars3Icon class="w-5 h-5" />
            </button>

            <!-- Date + Time (hidden on very small screens) -->
            <div class="hidden sm:flex flex-col">
                <div class="th-topbar-text text-sm font-semibold">{{ dateText }}</div>
                <div class="th-topbar-sub  text-xs">{{ timeText }}</div>
            </div>
        </div>

        <!-- Right: bell + user -->
        <div class="flex items-center gap-3">
            <!-- Bell -->
            <button class="th-topbar-icon th-topbar-icon-btn relative p-2 rounded-xl transition-colors">
                <BellIcon class="w-5 h-5" />
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white"></span>
            </button>

            <!-- User info -->
            <div class="flex items-center gap-2.5 pl-3 border-l th-divider">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center shrink-0">
                    <UserCircleIcon class="w-6 h-6 text-blue-600" />
                </div>
                <div class="hidden md:block leading-tight">
                    <div class="th-topbar-text text-sm font-semibold truncate max-w-[140px]">{{ auth.fullName }}</div>
                    <div class="flex items-center gap-1 text-[11px] text-emerald-600 mt-0.5">
                        <ShieldCheckIcon class="w-3 h-3" />
                        {{ roleLabel }}
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
