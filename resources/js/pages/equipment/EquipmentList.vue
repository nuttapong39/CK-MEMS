<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { equipmentsApi } from '../../api/equipments';
import { useMasterStore } from '../../stores/master';
import {
    PlusIcon, MagnifyingGlassIcon, ChevronLeftIcon, ChevronRightIcon,
    TrashIcon, EyeIcon,
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import EquipmentPreviewModal from '../../components/equipment/EquipmentPreviewModal.vue';

const auth   = useAuthStore();
const master = useMasterStore();

const loading = ref(false);
const items   = ref([]);
const meta    = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 });

// Preview modal
const previewOpen       = ref(false);
const previewEquipmentId = ref(null);

const filters = reactive({
    search: '',
    department_id: '',
    status: '',
    page: 1,
    per_page: 25,
});

const statusBadge = (s) => {
    const map = {
        ACTIVE:           { label: 'ใช้งาน',       cls: 'bg-emerald-100 text-emerald-700' },
        BROKEN:           { label: 'ชำรุด',         cls: 'bg-rose-100 text-rose-700' },
        UNDER_REPAIR:     { label: 'กำลังซ่อม',     cls: 'bg-amber-100 text-amber-700' },
        RETIRED:          { label: 'จำหน่ายแล้ว',   cls: 'bg-slate-100 text-slate-600' },
        PENDING_DISPOSAL: { label: 'รอแทงจำหน่าย', cls: 'bg-indigo-100 text-indigo-700' },
    };
    return map[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600' };
};

async function load() {
    loading.value = true;
    try {
        const params = {};
        for (const [k, v] of Object.entries(filters)) {
            if (v !== '' && v !== null && v !== undefined) params[k] = v;
        }
        const { data } = await equipmentsApi.list(params);
        items.value = data.data;
        meta.value  = data.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await master.loadAll();
    await load();
});

let debounceId = null;
watch(() => filters.search, () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => { filters.page = 1; load(); }, 300);
});
watch(() => [filters.department_id, filters.status, filters.per_page], () => { filters.page = 1; load(); });
watch(() => filters.page, load);

function openPreview(id) {
    previewEquipmentId.value = id;
    previewOpen.value = true;
}

function onSaved() {
    load(); // refresh list
}

async function handleDelete(item) {
    const r = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ?',
        text: `ลบเครื่อง ${item.id_code} - ${item.name_th}?`,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
    });
    if (!r.isConfirmed) return;
    try {
        await equipmentsApi.destroy(item.id);
        Swal.fire({ icon: 'success', title: 'ลบเรียบร้อย', timer: 1200, showConfirmButton: false });
        load();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ', text: e.response?.data?.message || '' });
    }
}

const pageRange = computed(() => {
    const cur = meta.value.current_page, last = meta.value.last_page;
    const start = Math.max(1, cur - 2), end = Math.min(last, cur + 2);
    const pages = [];
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});
</script>

<template>
    <div class="space-y-4">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800">รายการเครื่องมือแพทย์</h1>
                <p class="text-xs text-slate-500 mt-0.5">ทั้งหมด {{ meta.total }} รายการ</p>
            </div>
            <RouterLink
                v-if="auth.hasAnyRole(['admin', 'staff'])"
                :to="{ name: 'equipment.create' }"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl transition self-start text-sm"
            >
                <PlusIcon class="w-4 h-4" />
                เพิ่มเครื่องมือ
            </RouterLink>
        </div>

        <!-- Filters -->
        <div class="card-base p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative sm:col-span-2 lg:col-span-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="ค้นหา ID / ชื่อ / ยี่ห้อ / SN"
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition text-sm"
                    />
                </div>
                <select v-model.number="filters.department_id" class="px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-sm bg-white">
                    <option value="">ทุกหน่วยงาน</option>
                    <option v-for="d in master.departments" :key="d.id" :value="d.id">{{ d.code }} — {{ d.name_th }}</option>
                </select>
                <select v-model="filters.status" class="px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-sm bg-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="ACTIVE">ใช้งานอยู่</option>
                    <option value="BROKEN">ชำรุด</option>
                    <option value="UNDER_REPAIR">กำลังซ่อม</option>
                    <option value="RETIRED">จำหน่ายแล้ว</option>
                    <option value="PENDING_DISPOSAL">รอแทงจำหน่าย</option>
                </select>
                <select v-model.number="filters.per_page" class="px-3 py-2 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-sm bg-white">
                    <option :value="10">10 / หน้า</option>
                    <option :value="25">25 / หน้า</option>
                    <option :value="50">50 / หน้า</option>
                    <option :value="100">100 / หน้า</option>
                </select>
            </div>
        </div>

        <!-- Table (Desktop) / Cards (Mobile) -->

        <!-- Desktop table -->
        <div class="card-base overflow-hidden hidden sm:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3 font-semibold">ID Code</th>
                            <th class="px-4 py-3 font-semibold">ชื่อเครื่องมือ</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">หน่วยงาน</th>
                            <th class="px-4 py-3 font-semibold hidden lg:table-cell">ยี่ห้อ / รุ่น</th>
                            <th class="px-4 py-3 font-semibold">สถานะ</th>
                            <th class="px-4 py-3 font-semibold text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">กำลังโหลด...</td>
                        </tr>
                        <tr v-else-if="!items.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">ไม่พบเครื่องมือที่ตรงกับเงื่อนไข</td>
                        </tr>
                        <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-700">{{ item.id_code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ item.name_th }}</div>
                                <div v-if="item.name_en" class="text-xs text-slate-400">{{ item.name_en }}</div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 font-medium">
                                    {{ item.department?.code }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 hidden lg:table-cell">
                                <div>{{ item.manufacturer || '—' }}</div>
                                <div class="text-xs text-slate-400">{{ item.model || '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-1 rounded-full font-medium', statusBadge(item.status).cls]">
                                    {{ statusBadge(item.status).label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <!-- Preview (all roles) -->
                                    <button
                                        @click="openPreview(item.id)"
                                        title="ดูรายละเอียด"
                                        class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition"
                                    >
                                        <EyeIcon class="w-4 h-4" />
                                    </button>
                                    <!-- Delete (admin only) -->
                                    <button
                                        v-if="auth.isAdmin"
                                        @click="handleDelete(item)"
                                        title="ลบ"
                                        class="p-1.5 rounded-lg hover:bg-rose-50 text-rose-600 transition"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 gap-2 border-t border-slate-100">
                <div class="text-xs text-slate-500">
                    หน้า {{ meta.current_page }} / {{ meta.last_page }} ({{ meta.total }} รายการ)
                </div>
                <div class="flex items-center gap-1">
                    <button :disabled="filters.page === 1" @click="filters.page--"
                        class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        <ChevronLeftIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-for="p in pageRange" :key="p"
                        @click="filters.page = p"
                        :class="['min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition',
                            p === meta.current_page ? 'bg-blue-600 text-white' : 'border border-slate-200 hover:bg-slate-50']"
                    >{{ p }}</button>
                    <button :disabled="filters.page === meta.last_page" @click="filters.page++"
                        class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        <ChevronRightIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
            <div v-if="loading" class="text-center py-10 text-slate-400 text-sm">กำลังโหลด...</div>
            <div v-else-if="!items.length" class="text-center py-10 text-slate-400 text-sm card-base p-6">ไม่พบเครื่องมือ</div>
            <div
                v-for="item in items" :key="item.id"
                class="card-base p-4 space-y-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-mono text-xs font-semibold text-blue-700 mb-1">{{ item.id_code }}</div>
                        <div class="font-semibold text-slate-800 text-sm leading-tight">{{ item.name_th }}</div>
                        <div v-if="item.name_en" class="text-xs text-slate-400 mt-0.5">{{ item.name_en }}</div>
                    </div>
                    <span :class="['text-xs px-2 py-1 rounded-full font-medium shrink-0', statusBadge(item.status).cls]">
                        {{ statusBadge(item.status).label }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span v-if="item.department?.code">🏥 {{ item.department.code }}</span>
                    <span v-if="item.manufacturer">🔧 {{ item.manufacturer }}</span>
                    <span v-if="item.model">📋 {{ item.model }}</span>
                </div>
                <div class="flex gap-2 pt-1 border-t border-slate-100">
                    <button
                        @click="openPreview(item.id)"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition"
                    >
                        <EyeIcon class="w-4 h-4" />
                        {{ auth.hasAnyRole(['admin','staff']) ? 'ดู / แก้ไข' : 'ดูรายละเอียด' }}
                    </button>
                    <button
                        v-if="auth.isAdmin"
                        @click="handleDelete(item)"
                        class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition"
                    >
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Mobile pagination -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 py-2">
                <button :disabled="filters.page === 1" @click="filters.page--"
                    class="px-3 py-2 rounded-lg border border-slate-200 text-sm disabled:opacity-40">
                    ‹ ก่อนหน้า
                </button>
                <span class="text-sm text-slate-500">{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button :disabled="filters.page === meta.last_page" @click="filters.page++"
                    class="px-3 py-2 rounded-lg border border-slate-200 text-sm disabled:opacity-40">
                    ถัดไป ›
                </button>
            </div>
        </div>

        <!-- Equipment Preview/Edit Modal -->
        <EquipmentPreviewModal
            :open="previewOpen"
            :equipment-id="previewEquipmentId"
            @close="previewOpen = false"
            @saved="onSaved"
        />
    </div>
</template>
