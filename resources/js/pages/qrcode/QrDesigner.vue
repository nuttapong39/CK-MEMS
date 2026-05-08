<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
    QrCodeIcon, MagnifyingGlassIcon, PrinterIcon, BookmarkSquareIcon,
    Squares2X2Icon, ChevronLeftIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { useMasterStore } from '../../stores/master';
import { equipmentsApi } from '../../api/equipments';
import { qrcodeApi } from '../../api/qrcode';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const master = useMasterStore();

const items = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 });
const loading = ref(false);
const filters = reactive({ search: '', department_id: '', page: 1, per_page: 25 });
const selected = ref(new Set());

const layout = ref({
    name: 'A4 ขนาดมาตรฐาน',
    paper_size: 'a4',
    qr_size_mm: 35,
    fields_to_show: ['id_code', 'name_th', 'manufacturer', 'model', 'department'],
});

const templates = ref([]);

const allFields = [
    { key: 'id_code', label: 'รหัสเครื่องมือ (ID Code)' },
    { key: 'name_th', label: 'ชื่อภาษาไทย' },
    { key: 'name_en', label: 'ชื่อภาษาอังกฤษ' },
    { key: 'manufacturer', label: 'ยี่ห้อ' },
    { key: 'model', label: 'รุ่น' },
    { key: 'serial_number', label: 'Serial Number' },
    { key: 'department', label: 'หน่วยงาน' },
];

async function load() {
    loading.value = true;
    try {
        const params = {};
        for (const [k, v] of Object.entries(filters)) {
            if (v !== '' && v !== null) params[k] = v;
        }
        const { data } = await equipmentsApi.list(params);
        items.value = data.data;
        meta.value = data.meta;
    } finally {
        loading.value = false;
    }
}

async function loadTemplates() {
    const { data } = await qrcodeApi.templates();
    templates.value = data;
    const def = data.find((t) => t.is_default);
    if (def) applyTemplate(def);
}

onMounted(async () => {
    await master.loadAll();
    await Promise.all([load(), loadTemplates()]);
});

let debounceId = null;
watch(() => filters.search, () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => { filters.page = 1; load(); }, 300);
});
watch(() => [filters.department_id, filters.per_page], () => { filters.page = 1; load(); });
watch(() => filters.page, load);

function toggleSelect(id) {
    const s = new Set(selected.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selected.value = s;
}
const isSelected = (id) => selected.value.has(id);

function selectAllOnPage() {
    const s = new Set(selected.value);
    items.value.forEach((it) => s.add(it.id));
    selected.value = s;
}
function clearSelection() {
    selected.value = new Set();
}

function applyTemplate(t) {
    layout.value = {
        name: t.name,
        paper_size: t.paper_size,
        qr_size_mm: t.qr_size_mm,
        fields_to_show: Array.isArray(t.fields_to_show) ? [...t.fields_to_show] : t.fields_to_show,
    };
}

async function saveTemplate() {
    const { value: name } = await Swal.fire({
        title: 'บันทึก Template',
        input: 'text',
        inputLabel: 'ชื่อ template',
        inputValue: layout.value.name,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#2563eb',
    });
    if (!name) return;
    try {
        await qrcodeApi.storeTemplate({ ...layout.value, name, is_default: false });
        Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1200, showConfirmButton: false });
        loadTemplates();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: e.response?.data?.message || '' });
    }
}

async function generatePdf() {
    if (selected.value.size === 0) {
        Swal.fire({ icon: 'warning', title: 'เลือกเครื่องมืออย่างน้อย 1 รายการ' });
        return;
    }
    if (layout.value.fields_to_show.length === 0) {
        Swal.fire({ icon: 'warning', title: 'เลือกอย่างน้อย 1 field ที่จะแสดง' });
        return;
    }
    try {
        const { data } = await qrcodeApi.batchPdf({
            equipment_ids: Array.from(selected.value),
            paper_size: layout.value.paper_size,
            qr_size_mm: layout.value.qr_size_mm,
            fields_to_show: layout.value.fields_to_show,
        });
        const blob = new Blob([data], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'สร้าง PDF ไม่สำเร็จ', text: e.response?.data?.message || '' });
    }
}

const previewItems = computed(() => items.value.filter((it) => selected.value.has(it.id)).slice(0, 6));

const pageRange = computed(() => {
    const cur = meta.value.current_page, last = meta.value.last_page;
    const start = Math.max(1, cur - 2), end = Math.min(last, cur + 2);
    const arr = [];
    for (let i = start; i <= end; i++) arr.push(i);
    return arr;
});

const pngPreviewUrl = (id) => `${qrcodeApi.pngUrl(id, 240)}&_t=${Date.now()}`;
</script>

<template>
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center">
                    <QrCodeIcon class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">QR Code Designer</h1>
                    <p class="text-xs text-slate-500 mt-0.5">เลือกเครื่องมือ → กำหนดขนาด/fields → Preview/พิมพ์</p>
                </div>
            </div>
            <div class="text-sm text-slate-500">
                เลือกแล้ว <span class="font-bold text-blue-600">{{ selected.size }}</span> / {{ meta.total }}
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- LEFT: Equipment list (selection) -->
            <div class="lg:col-span-7 space-y-3">
                <div class="card-base p-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="relative sm:col-span-2">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="filters.search" placeholder="ค้นหา ID / ชื่อ / ยี่ห้อ"
                                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                        </div>
                        <select v-model.number="filters.department_id" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                            <option value="">ทุกหน่วยงาน</option>
                            <option v-for="d in master.departments" :key="d.id" :value="d.id">{{ d.code }} — {{ d.name_th }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 mt-3 text-xs">
                        <button @click="selectAllOnPage" class="text-blue-600 hover:underline">เลือกทั้งหน้า</button>
                        <span class="text-slate-300">|</span>
                        <button @click="clearSelection" class="text-rose-600 hover:underline">ล้าง</button>
                    </div>
                </div>

                <div class="card-base overflow-hidden">
                    <div v-if="loading" class="p-12 text-center text-slate-400 text-sm">กำลังโหลด...</div>
                    <div v-else-if="!items.length" class="p-12 text-center text-slate-400 text-sm">ไม่พบเครื่องมือ</div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="it in items" :key="it.id" class="px-4 py-2 flex items-center gap-3 hover:bg-slate-50 cursor-pointer transition" @click="toggleSelect(it.id)">
                            <input type="checkbox" :checked="isSelected(it.id)" class="w-4 h-4 accent-blue-600" />
                            <div class="font-mono text-xs text-blue-700 w-32 shrink-0">{{ it.id_code }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-slate-800 truncate">{{ it.name_th }}</div>
                                <div class="text-xs text-slate-500">{{ it.manufacturer }} {{ it.model }}</div>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ it.department?.code }}</span>
                        </li>
                    </ul>
                    <div v-if="meta.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                        <div class="text-xs text-slate-500">หน้า {{ meta.current_page }} / {{ meta.last_page }}</div>
                        <div class="flex items-center gap-1">
                            <button :disabled="filters.page === 1" @click="filters.page--" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40">
                                <ChevronLeftIcon class="w-4 h-4" />
                            </button>
                            <button v-for="p in pageRange" :key="p" @click="filters.page = p"
                                :class="['min-w-[28px] h-7 px-2 rounded-lg text-xs', p === meta.current_page ? 'bg-blue-600 text-white' : 'border border-slate-200']">{{ p }}</button>
                            <button :disabled="filters.page === meta.last_page" @click="filters.page++" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40">
                                <ChevronRightIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Designer + Preview -->
            <div class="lg:col-span-5 space-y-3">
                <div class="card-base p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-slate-800">การตั้งค่าใบ QR</div>
                        <select v-if="templates.length" @change="applyTemplate(templates.find(t => t.id === parseInt($event.target.value)))" class="text-xs px-2 py-1 rounded-lg border border-slate-200">
                            <option value="">โหลด template...</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500 block mb-1">ขนาดกระดาษ</label>
                            <select v-model="layout.paper_size" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                                <option value="a4">A4 (210×297)</option>
                                <option value="a5">A5 (148×210)</option>
                                <option value="letter">Letter</option>
                                <option value="legal">Legal</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 block mb-1">QR Size: <span class="font-bold text-blue-600">{{ layout.qr_size_mm }} mm</span></label>
                            <input v-model.number="layout.qr_size_mm" type="range" min="20" max="80" step="1" class="w-full accent-blue-600" />
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-slate-500 block mb-2">Fields ที่จะแสดงบนใบ</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <label v-for="f in allFields" :key="f.key" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" :value="f.key" v-model="layout.fields_to_show" class="w-3.5 h-3.5 accent-blue-600" />
                                <span class="text-xs text-slate-700">{{ f.label }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-3 border-t border-slate-100">
                        <button @click="saveTemplate" class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl border-2 border-blue-200 text-blue-700 text-sm font-medium hover:bg-blue-50">
                            <BookmarkSquareIcon class="w-4 h-4" /> บันทึก Template
                        </button>
                        <button @click="generatePdf" class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white text-sm font-semibold shadow disabled:opacity-50" :disabled="selected.size === 0">
                            <PrinterIcon class="w-4 h-4" /> สร้าง PDF
                        </button>
                    </div>
                </div>

                <!-- Preview -->
                <div class="card-base p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <Squares2X2Icon class="w-4 h-4 text-violet-500" />
                        <div class="text-sm font-semibold text-slate-800">Preview ({{ Math.min(selected.size, 6) }} ตัวอย่าง)</div>
                    </div>
                    <div v-if="!previewItems.length" class="py-12 text-center text-slate-400 text-sm">เลือกเครื่องมือก่อน</div>
                    <div v-else class="grid grid-cols-2 gap-2">
                        <div v-for="it in previewItems" :key="it.id" class="border border-dashed border-slate-300 rounded-xl p-2 text-center bg-white">
                            <img :src="pngPreviewUrl(it.id)" class="block mx-auto mb-1" :style="{ width: layout.qr_size_mm * 2 + 'px', height: layout.qr_size_mm * 2 + 'px' }" />
                            <div class="font-mono text-[10px] font-bold text-blue-700">{{ it.id_code }}</div>
                            <div v-if="layout.fields_to_show.includes('name_th')" class="text-[10px] text-slate-700 truncate">{{ it.name_th }}</div>
                            <div v-if="layout.fields_to_show.includes('manufacturer')" class="text-[9px] text-slate-500 truncate">{{ it.manufacturer }}</div>
                            <div v-if="layout.fields_to_show.includes('department')" class="text-[9px] text-slate-400">{{ it.department?.name_th }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
