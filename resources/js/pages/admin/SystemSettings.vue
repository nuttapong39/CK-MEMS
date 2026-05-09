<script setup>
import { ref, onMounted } from 'vue';
import {
    Cog6ToothIcon, PhotoIcon, BuildingOffice2Icon, ArrowUpTrayIcon, SwatchIcon,
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import client from '../../api/client';
import { useSettingsStore } from '../../stores/settings';

const settingsStore = useSettingsStore();

const loading       = ref(false);
const saving        = ref(false);
const savingTheme   = ref(false);
const uploadingLogo = ref(false);

const settings = ref({
    system_name: 'CK-MEMS',
    hospital_name: '',
    logo_url: null,
    theme: 'light',
});

const systemNameInput = ref('');
const selectedTheme   = ref('light');
const logoPreview     = ref(null);
const fileInput       = ref(null);

// ── Theme definitions (for the picker UI) ────────────────────────
const themes = [
    {
        id: 'light',
        label: 'Light',
        labelTh: 'สว่าง',
        desc: 'ธีมสว่างมาตรฐาน',
        sidebarBg: '#ffffff',
        bodyBg: '#f8fafc',
        topbarBg: '#ffffff',
        cardBg: '#f1f5f9',
        logoFrom: '#3b82f6',
        logoTo: '#6366f1',
        primary: '#2563eb',
        navText: '#475569',
        activeItem: '#dbeafe',
    },
    {
        id: 'dark',
        label: 'Dark',
        labelTh: 'มืด',
        desc: 'ธีมมืดสบายตา',
        sidebarBg: '#1e293b',
        bodyBg: '#0f172a',
        topbarBg: '#1e293b',
        cardBg: '#334155',
        logoFrom: '#2563eb',
        logoTo: '#4f46e5',
        primary: '#3b82f6',
        navText: '#94a3b8',
        activeItem: 'rgba(59,130,246,0.35)',
    },
    {
        id: 'pastel',
        label: 'Pastel',
        labelTh: 'พาสเทล',
        desc: 'ธีมสีอ่อนหวาน',
        sidebarBg: '#faf5ff',
        bodyBg: '#f5f3ff',
        topbarBg: '#fdf4ff',
        cardBg: '#ffffff',
        logoFrom: '#8b5cf6',
        logoTo: '#ec4899',
        primary: '#7c3aed',
        navText: '#6d28d9',
        activeItem: '#ede9fe',
    },
    {
        id: 'classic',
        label: 'Classic',
        labelTh: 'คลาสสิก',
        desc: 'ธีมสีเขียวคลาสสิก',
        sidebarBg: '#134e4a',
        bodyBg: '#f0fdf4',
        topbarBg: '#ffffff',
        cardBg: '#f0fdf4',
        logoFrom: '#0d9488',
        logoTo: '#059669',
        primary: '#0d9488',
        navText: '#99f6e4',
        activeItem: 'rgba(20,184,166,0.35)',
    },
];

// ── Load ──────────────────────────────────────────────────────────
async function load() {
    loading.value = true;
    try {
        const { data } = await client.get('/system/settings');
        settings.value       = data;
        systemNameInput.value = data.system_name ?? 'CK-MEMS';
        logoPreview.value    = data.logo_url ?? null;
        selectedTheme.value  = data.theme ?? 'light';
    } catch {
        Swal.fire({ icon: 'error', title: 'โหลดข้อมูลไม่สำเร็จ', confirmButtonColor: '#ef4444' });
    } finally {
        loading.value = false;
    }
}

onMounted(load);

// ── Save system name ──────────────────────────────────────────────
async function saveSettings() {
    if (!systemNameInput.value.trim()) {
        Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่อระบบ', confirmButtonColor: '#3b82f6' });
        return;
    }
    saving.value = true;
    try {
        const { data } = await client.put('/system/settings', {
            system_name: systemNameInput.value.trim(),
        });
        settings.value.system_name = data.system_name;
        settingsStore.update(data.system_name);
        Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', text: 'ชื่อระบบถูกอัพเดตแล้ว', timer: 1800, showConfirmButton: false });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: e.response?.data?.message ?? '', confirmButtonColor: '#ef4444' });
    } finally {
        saving.value = false;
    }
}

// ── Save theme ────────────────────────────────────────────────────
async function saveTheme() {
    savingTheme.value = true;
    try {
        const { data } = await client.put('/system/settings', {
            theme: selectedTheme.value,
        });
        settings.value.theme = data.theme;
        // Update store → applies instantly to sidebar, topbar, body
        settingsStore.update(undefined, undefined, data.theme);
        Swal.fire({ icon: 'success', title: 'เปลี่ยนธีมเรียบร้อย', timer: 1400, showConfirmButton: false });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: e.response?.data?.message ?? '', confirmButtonColor: '#ef4444' });
    } finally {
        savingTheme.value = false;
    }
}

// ── Logo upload ───────────────────────────────────────────────────
function triggerFilePicker() { fileInput.value?.click(); }

function onFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        Swal.fire({ icon: 'warning', title: 'ไฟล์ต้องเป็นรูปภาพเท่านั้น', confirmButtonColor: '#3b82f6' });
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        Swal.fire({ icon: 'warning', title: 'ไฟล์ต้องมีขนาดไม่เกิน 2 MB', confirmButtonColor: '#3b82f6' });
        return;
    }
    const reader = new FileReader();
    reader.onload = (ev) => { logoPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
    uploadLogo(file);
}

async function uploadLogo(file) {
    uploadingLogo.value = true;
    try {
        const form = new FormData();
        form.append('logo', file);
        const { data } = await client.post('/system/settings/logo', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        settings.value.logo_url = data.logo_url;
        logoPreview.value = data.logo_url;
        settingsStore.update(undefined, data.logo_url);
        Swal.fire({ icon: 'success', title: 'อัพโหลดโลโก้เรียบร้อย!', timer: 1600, showConfirmButton: false });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'อัพโหลดไม่สำเร็จ', text: e.response?.data?.message ?? '', confirmButtonColor: '#ef4444' });
        logoPreview.value = settings.value.logo_url;
    } finally {
        uploadingLogo.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
}
</script>

<template>
    <div class="max-w-3xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                <Cog6ToothIcon class="w-5 h-5 text-slate-600" />
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800">ตั้งค่าระบบ</h1>
                <p class="text-sm text-slate-500">ปรับแต่งข้อมูลโรงพยาบาลและการแสดงผลระบบ</p>
            </div>
        </div>

        <div v-if="loading" class="card-base p-12 text-center text-slate-400">กำลังโหลด...</div>

        <template v-else>

            <!-- ─── โลโก้โรงพยาบาล ─────────────────────────────────── -->
            <div class="card-base p-6">
                <div class="flex items-center gap-2 mb-5">
                    <PhotoIcon class="w-5 h-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-800">โลโก้โรงพยาบาล</h2>
                </div>

                <div class="flex flex-col sm:flex-row items-start gap-6">
                    <!-- Preview -->
                    <div class="shrink-0">
                        <div class="w-32 h-32 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden relative">
                            <img v-if="logoPreview" :src="logoPreview" alt="Hospital Logo" class="w-full h-full object-contain p-2" />
                            <div v-else class="text-center text-slate-400">
                                <PhotoIcon class="w-10 h-10 mx-auto opacity-30" />
                                <p class="text-xs mt-1">ยังไม่มีโลโก้</p>
                            </div>
                            <div v-if="uploadingLogo" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-2xl">
                                <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Upload area -->
                    <div class="flex-1 space-y-3">
                        <p class="text-sm text-slate-600">
                            อัพโหลดโลโก้โรงพยาบาลในรูปแบบ JPG, PNG, SVG, WebP<br>
                            <span class="text-slate-400 text-xs">ขนาดแนะนำ: 256×256px ขึ้นไป ไม่เกิน 2 MB</span>
                        </p>
                        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
                        <div
                            @click="triggerFilePicker"
                            @dragover.prevent
                            @drop.prevent="(e) => onFileChange({ target: { files: e.dataTransfer.files } })"
                            class="border-2 border-dashed border-blue-200 bg-blue-50/50 rounded-xl p-6 flex flex-col items-center gap-2 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition"
                        >
                            <ArrowUpTrayIcon class="w-8 h-8 text-blue-400" />
                            <p class="text-sm text-blue-600 font-medium">คลิกเพื่อเลือกไฟล์ หรือลากมาวาง</p>
                            <p class="text-xs text-slate-400">JPG, PNG, SVG, WebP — สูงสุด 2 MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── ธีมระบบ ──────────────────────────────────────────── -->
            <div class="card-base p-6">
                <div class="flex items-center gap-2 mb-5">
                    <SwatchIcon class="w-5 h-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-800">ธีมระบบ</h2>
                </div>

                <!-- Theme picker grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                    <button
                        v-for="t in themes"
                        :key="t.id"
                        type="button"
                        @click="selectedTheme = t.id"
                        :class="[
                            'rounded-2xl border-2 overflow-hidden transition-all duration-150 text-left focus:outline-none',
                            selectedTheme === t.id
                                ? 'border-blue-500 shadow-lg shadow-blue-500/20 ring-2 ring-blue-400/30 scale-[1.02]'
                                : 'border-slate-200 hover:border-slate-300 hover:shadow-md',
                        ]"
                    >
                        <!-- Mini preview -->
                        <div class="flex h-24">
                            <!-- Mini sidebar -->
                            <div
                                class="w-9 flex flex-col items-center gap-1.5 py-2.5 px-1.5 shrink-0"
                                :style="{ backgroundColor: t.sidebarBg, borderRight: '1px solid rgba(0,0,0,0.06)' }"
                            >
                                <!-- Logo circle -->
                                <div
                                    class="w-5 h-5 rounded-md"
                                    :style="{ background: `linear-gradient(135deg, ${t.logoFrom}, ${t.logoTo})` }"
                                ></div>
                                <!-- Nav items -->
                                <div class="w-full space-y-1 mt-0.5">
                                    <div
                                        class="h-1.5 rounded-sm"
                                        :style="{ backgroundColor: t.activeItem }"
                                    ></div>
                                    <div
                                        class="h-1.5 rounded-sm opacity-40"
                                        :style="{ backgroundColor: t.navText }"
                                    ></div>
                                    <div
                                        class="h-1.5 rounded-sm opacity-40"
                                        :style="{ backgroundColor: t.navText }"
                                    ></div>
                                    <div
                                        class="h-1.5 rounded-sm opacity-40"
                                        :style="{ backgroundColor: t.navText }"
                                    ></div>
                                </div>
                            </div>
                            <!-- Mini content -->
                            <div
                                class="flex-1 flex flex-col"
                                :style="{ backgroundColor: t.bodyBg }"
                            >
                                <!-- Mini topbar -->
                                <div
                                    class="h-5 w-full shrink-0"
                                    :style="{ backgroundColor: t.topbarBg, borderBottom: '1px solid rgba(0,0,0,0.06)' }"
                                ></div>
                                <!-- Mini cards -->
                                <div class="flex-1 p-1.5 grid grid-cols-2 gap-1 content-start">
                                    <div
                                        class="h-4 rounded"
                                        :style="{ backgroundColor: t.cardBg, boxShadow: '0 1px 2px rgba(0,0,0,0.06)' }"
                                    ></div>
                                    <div
                                        class="h-4 rounded"
                                        :style="{ backgroundColor: t.cardBg, boxShadow: '0 1px 2px rgba(0,0,0,0.06)' }"
                                    ></div>
                                    <div
                                        class="h-4 rounded col-span-2"
                                        :style="{ backgroundColor: t.cardBg, boxShadow: '0 1px 2px rgba(0,0,0,0.06)' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Label footer -->
                        <div
                            class="py-2 px-3 flex items-center justify-between transition-colors duration-150"
                            :style="{
                                backgroundColor: selectedTheme === t.id ? t.primary : '#f8fafc',
                            }"
                        >
                            <div>
                                <div
                                    class="text-xs font-semibold leading-tight"
                                    :style="{ color: selectedTheme === t.id ? '#fff' : '#374151' }"
                                >{{ t.label }}</div>
                                <div
                                    class="text-[10px] leading-tight mt-0.5"
                                    :style="{ color: selectedTheme === t.id ? 'rgba(255,255,255,0.75)' : '#9ca3af' }"
                                >{{ t.labelTh }}</div>
                            </div>
                            <!-- Check indicator -->
                            <div
                                v-if="selectedTheme === t.id"
                                class="w-4 h-4 rounded-full bg-white/30 flex items-center justify-center shrink-0"
                            >
                                <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Theme description -->
                <p class="text-xs text-slate-400 mb-4">
                    ธีมที่เลือก:
                    <span class="font-semibold text-slate-600">
                        {{ themes.find(t => t.id === selectedTheme)?.label }}
                        ({{ themes.find(t => t.id === selectedTheme)?.desc }})
                    </span>
                </p>

                <div class="flex justify-end">
                    <button
                        @click="saveTheme"
                        :disabled="savingTheme"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg v-if="savingTheme" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <SwatchIcon v-else class="w-4 h-4" />
                        {{ savingTheme ? 'กำลังบันทึก...' : 'ใช้งานธีมนี้' }}
                    </button>
                </div>
            </div>

            <!-- ─── ชื่อระบบ ─────────────────────────────────────────── -->
            <div class="card-base p-6">
                <div class="flex items-center gap-2 mb-5">
                    <BuildingOffice2Icon class="w-5 h-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-800">ชื่อระบบ</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700 block mb-1.5">
                            ชื่อแสดงในระบบ (System Name)
                        </label>
                        <input
                            v-model="systemNameInput"
                            type="text"
                            maxlength="100"
                            placeholder="เช่น CK-MEMS หรือชื่อโรงพยาบาล"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                        />
                        <p class="text-xs text-slate-400 mt-1">ชื่อนี้จะแสดงใน Sidebar และ Topbar ของระบบ</p>
                    </div>

                    <!-- Preview -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <p class="text-xs text-slate-400 mb-2">Preview</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                CK
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-800">{{ systemNameInput || 'CK-MEMS' }}</div>
                                <div class="text-[10px] text-slate-400">Medical Equipment</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            @click="saveSettings"
                            :disabled="saving"
                            class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
                        </button>
                    </div>
                </div>
            </div>

        </template>
    </div>
</template>
