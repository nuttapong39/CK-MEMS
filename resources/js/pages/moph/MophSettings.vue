<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
    BellAlertIcon, CheckBadgeIcon, BeakerIcon, DocumentTextIcon,
    PaperAirplaneIcon, ListBulletIcon, KeyIcon,
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { mophApi } from '../../api/moph';

const settings = ref(null);
const loading = ref(true);
const saving = ref(false);
const testing = ref(false);

const form = ref({
    is_enabled: false,
    endpoint_url: '',
    client_key: '',
    secret_key: '',
    notify_on_create_equipment: true,
    notify_on_repair_request: true,
    notify_on_repair_progress: true,
    notify_on_calibration: true,
});

async function load() {
    loading.value = true;
    try {
        const { data } = await mophApi.settings();
        settings.value = data;
        form.value = {
            is_enabled: data.is_enabled,
            endpoint_url: data.endpoint_url,
            client_key: data.client_key || '',
            secret_key: '',
            notify_on_create_equipment: data.notify_on_create_equipment,
            notify_on_repair_request: data.notify_on_repair_request,
            notify_on_repair_progress: data.notify_on_repair_progress,
            notify_on_calibration: data.notify_on_calibration,
        };
    } finally {
        loading.value = false;
    }
}
onMounted(load);

async function save() {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (!payload.secret_key) delete payload.secret_key;
        await mophApi.updateSettings(payload);
        Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', timer: 1200, showConfirmButton: false });
        load();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: e.response?.data?.message || '' });
    } finally {
        saving.value = false;
    }
}

async function sendTest() {
    testing.value = true;
    try {
        const { data } = await mophApi.test();
        await Swal.fire({
            icon: data.ok ? 'success' : 'error',
            title: data.ok ? 'ทดสอบสำเร็จ' : 'ทดสอบไม่สำเร็จ',
            html: `<pre class="text-xs text-left">${(data.body || data.message || '').slice(0, 500)}</pre>`,
            confirmButtonColor: '#2563eb',
        });
        load();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ส่งทดสอบไม่ได้', text: e.response?.data?.message || '' });
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">ตั้งค่า MOPH Alert</h1>
                <p class="text-xs text-slate-500 mt-0.5">ระบบแจ้งเตือนผ่าน LINE Flex Message ของ MOPH</p>
            </div>
            <div class="flex gap-2">
                <RouterLink :to="{ name: 'moph.flex' }" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 hover:bg-white text-sm font-medium text-slate-700">
                    <DocumentTextIcon class="w-4 h-4" /> Flex Designer
                </RouterLink>
                <RouterLink :to="{ name: 'moph.logs' }" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 hover:bg-white text-sm font-medium text-slate-700">
                    <ListBulletIcon class="w-4 h-4" /> Logs
                </RouterLink>
            </div>
        </div>

        <div v-if="loading" class="card-base p-12 text-center text-slate-400">กำลังโหลด...</div>

        <template v-else>
            <!-- Master toggle -->
            <div class="card-base p-6 flex items-center gap-5">
                <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center', form.is_enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400']">
                    <BellAlertIcon class="w-7 h-7" />
                </div>
                <div class="flex-1">
                    <div class="text-sm font-semibold text-slate-800">เปิดใช้งานการแจ้งเตือน MOPH Alert</div>
                    <div class="text-xs text-slate-500 mt-0.5">เมื่อปิด ระบบจะไม่ส่งแจ้งเตือนใดๆ แม้ว่าจะตั้งค่าตัวเลือกย่อยไว้</div>
                </div>
                <label class="inline-flex items-center cursor-pointer">
                    <input v-model="form.is_enabled" type="checkbox" class="sr-only peer" />
                    <div class="relative w-12 h-7 bg-slate-200 peer-checked:bg-emerald-500 rounded-full transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-transform peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            <!-- Per-event toggles -->
            <div class="card-base p-6">
                <div class="text-sm font-semibold text-slate-800 mb-4">เลือกเหตุการณ์ที่ต้องการแจ้ง</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label v-for="(item, idx) in [
                        { key: 'notify_on_create_equipment', label: 'เพิ่มเครื่องมือใหม่', icon: '🩺' },
                        { key: 'notify_on_repair_request', label: 'มีการแจ้งซ่อม', icon: '🔧' },
                        { key: 'notify_on_repair_progress', label: 'อัปเดตสถานะการซ่อม', icon: '📋' },
                        { key: 'notify_on_calibration', label: 'การสอบเทียบ', icon: '🧪' },
                    ]" :key="idx" class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition cursor-pointer">
                        <input v-model="form[item.key]" type="checkbox" class="w-4 h-4 accent-blue-600" />
                        <span class="text-2xl">{{ item.icon }}</span>
                        <span class="text-sm text-slate-700">{{ item.label }}</span>
                    </label>
                </div>
            </div>

            <!-- API credentials -->
            <div class="card-base p-6">
                <div class="flex items-center gap-2 text-blue-600 mb-4">
                    <KeyIcon class="w-5 h-5" />
                    <h2 class="text-sm font-semibold text-slate-700">API Credentials</h2>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-slate-700 block mb-1">Endpoint URL</label>
                        <input v-model="form.endpoint_url" type="url"
                            placeholder="https://morpromt2f.moph.go.th/api/notify/send"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700 block mb-1">Client Key</label>
                        <input v-model="form.client_key" type="text"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700 block mb-1">
                            Secret Key
                            <span v-if="settings?.has_secret_key" class="text-emerald-600 text-[10px] font-semibold ml-2">บันทึกแล้ว</span>
                        </label>
                        <input v-model="form.secret_key" type="password"
                            placeholder="เว้นไว้ถ้าไม่ต้องการเปลี่ยน"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:border-blue-500" />
                    </div>
                </div>
            </div>

            <!-- Last test -->
            <div v-if="settings?.last_test_at" class="card-base p-4 flex items-center gap-3">
                <CheckBadgeIcon class="w-5 h-5 text-blue-500" />
                <div class="text-xs text-slate-600 flex-1">
                    ทดสอบล่าสุด: {{ new Date(settings.last_test_at).toLocaleString('th-TH') }}
                </div>
                <span :class="['text-xs px-2 py-1 rounded-full', settings.last_test_status?.includes('OK') ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
                    {{ settings.last_test_status || '—' }}
                </span>
            </div>

            <!-- Action buttons -->
            <div class="flex justify-between gap-3">
                <button
                    @click="sendTest"
                    :disabled="testing || !form.is_enabled"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border-2 border-blue-200 text-blue-700 hover:bg-blue-50 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <PaperAirplaneIcon class="w-5 h-5" />
                    {{ testing ? 'กำลังทดสอบ...' : 'ส่งข้อความทดสอบ' }}
                </button>
                <button
                    @click="save"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl transition disabled:opacity-50"
                >
                    {{ saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
                </button>
            </div>
        </template>
    </div>
</template>
