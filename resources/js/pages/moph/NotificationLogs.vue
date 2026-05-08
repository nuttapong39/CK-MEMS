<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import { mophApi } from '../../api/moph';

const logs = ref([]);
const loading = ref(true);

async function load() {
    loading.value = true;
    try {
        const { data } = await mophApi.logs({ limit: 100 });
        logs.value = data;
    } finally {
        loading.value = false;
    }
}
onMounted(load);

function statusBadge(code) {
    if (!code) return 'bg-slate-100 text-slate-500';
    if (code >= 200 && code < 300) return 'bg-emerald-100 text-emerald-700';
    return 'bg-rose-100 text-rose-700';
}
</script>

<template>
    <div class="max-w-5xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <RouterLink :to="{ name: 'moph.settings' }" class="p-2 rounded-xl hover:bg-white hover:shadow-sm transition">
                    <ArrowLeftIcon class="w-5 h-5 text-slate-600" />
                </RouterLink>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">MOPH Alert Logs</h1>
                    <p class="text-xs text-slate-500 mt-0.5">บันทึกการส่งแจ้งเตือน 100 รายการล่าสุด</p>
                </div>
            </div>
            <button @click="load" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 hover:bg-white text-sm">
                <ArrowPathIcon class="w-4 h-4" /> รีเฟรช
            </button>
        </div>

        <div class="card-base overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3 font-semibold">เวลา</th>
                            <th class="px-5 py-3 font-semibold">Template</th>
                            <th class="px-5 py-3 font-semibold">Event</th>
                            <th class="px-5 py-3 font-semibold">HTTP</th>
                            <th class="px-5 py-3 font-semibold">Response</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">กำลังโหลด...</td>
                        </tr>
                        <tr v-else-if="!logs.length">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">ยังไม่มี logs</td>
                        </tr>
                        <tr v-for="l in logs" :key="l.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap">
                                {{ new Date(l.sent_at).toLocaleString('th-TH') }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs font-mono px-2 py-1 rounded-full bg-slate-100 text-slate-700">{{ l.template_key }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600 font-mono truncate max-w-[200px]">{{ l.event_signature || '—' }}</td>
                            <td class="px-5 py-3">
                                <span :class="['text-xs px-2 py-1 rounded-full font-medium', statusBadge(l.response_code)]">
                                    {{ l.response_code || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600 truncate max-w-[300px]">{{ l.response_body || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
