<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { BellIcon, UserCircleIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '../../stores/auth';
import { formatThaiFullDate, formatThaiTime } from '../../utils/thaiDate';

const auth = useAuthStore();
const now = ref(new Date());

let timerId;
onMounted(() => {
    timerId = setInterval(() => { now.value = new Date(); }, 1000);
});
onUnmounted(() => clearInterval(timerId));

const dateText = computed(() => formatThaiFullDate(now.value));
const timeText = computed(() => `เวลา ${formatThaiTime(now.value)}`);

const roleLabel = computed(() => {
    if (auth.isAdmin) return 'ADMIN';
    if (auth.isStaff) return 'STAFF';
    return 'USER';
});
</script>

<template>
    <header class="h-[72px] bg-white border-b border-slate-200 px-6 lg:px-8 flex items-center justify-between">
        <!-- Date + Time -->
        <div class="flex flex-col">
            <div class="text-sm font-semibold text-slate-800">{{ dateText }}</div>
            <div class="text-xs text-slate-500">{{ timeText }}</div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Bell -->
            <button class="relative p-2 rounded-xl hover:bg-slate-50 transition-colors">
                <BellIcon class="w-5 h-5 text-slate-500" />
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white"></span>
            </button>

            <!-- User -->
            <div class="flex items-center gap-3 pl-4 border-l border-slate-100">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                    <UserCircleIcon class="w-7 h-7 text-blue-600" />
                </div>
                <div class="hidden sm:block leading-tight">
                    <div class="text-sm font-semibold text-slate-800">{{ auth.fullName }}</div>
                    <div class="flex items-center gap-1 text-[11px] text-emerald-600 mt-0.5">
                        <ShieldCheckIcon class="w-3.5 h-3.5" />
                        สิทธิ์การใช้งาน: {{ roleLabel }}
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
