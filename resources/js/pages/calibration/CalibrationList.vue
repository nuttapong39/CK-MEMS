<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
    PlusIcon, BeakerIcon, CheckCircleIcon, XCircleIcon,
    ChevronLeftIcon, ChevronRightIcon, ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import { calibrationsApi } from '../../api/calibrations';
import { useMasterStore } from '../../stores/master';
import { useAuthStore } from '../../stores/auth';
import CalibrationCreateModal from '../../components/calibration/CalibrationCreateModal.vue';

const auth = useAuthStore();
const master = useMasterStore();

const loading = ref(false);
const items = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 });
const summary = ref({ this_year: 0, pass_rate: null, due_soon: 0, overdue: 0 });
const showCreateModal = ref(false);

const filters = reactive({
    department_id: '',
    equipment_id: '',
    result: '',
    page: 1,
    per_page: 25,
});

async function load() {
    loading.value = true;
    try {
        const params = {};
        for (const [k, v] of Object.entries(filters)) {
            if (v !== '' && v !== null && v !== undefined) params[k] = v;
        }
        const [list, sum] = await Promise.all([
            calibrationsApi.list(params),
            calibrationsApi.summary(),
        ]);
        items.value = list.data.data;
        meta.value = list.data.meta;
        summary.value = sum.data;
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await master.loadAll();
    await load();
});

watch(() => [filters.department_id, filters.equipment_id, filters.result, filters.per_page], () => {
    filters.page = 1;
    load();
});
watch(() => filters.page, load);

const pageRange = computed(() => {
    const cur = meta.value.current_page;
    const last = meta.value.last_page;
    const start = Math.max(1, cur - 2);
    const end = Math.min(last, cur + 2);
    const pages = [];
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});

function onCreated() {
    showCreateModal.value = false;
    load();
}
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800">การสอบเทียบเครื่องมือ</h1>
                <p class="text-xs text-slate-500 mt-0.5">ทั้งหมด {{ meta.total }} รายการ</p>
            </div>
            <button
                v-if="auth.hasAnyRole(['admin','staff'])"
                @click="showCreateModal = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-xl transition self-start"
            >
                <PlusIcon class="w-5 h-5" />
                เพิ่มการสอบเทียบ
            </button>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card-base p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center"><BeakerIcon class="w-6 h-6" /></div>
                <div>
                    <div class="text-xs text-slate-500">สอบเทียบในปีนี้</div>
                    <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ summary.this_year }}</div>
                </div>
            </div>
            <div class="card-base p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><CheckCircleIcon class="w-6 h-6" /></div>
                <div>
                    <div class="text-xs text-slate-500">อัตราผ่าน (ปีนี้)</div>
                    <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ summary.pass_rate !== null ? summary.pass_rate + '%' : '—' }}</div>
                </div>
            </div>
            <div class="card-base p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">⏳</div>
                <div>
                    <div class="text-xs text-slate-500">ใกล้ครบ (30 วัน)</div>
                    <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ summary.due_soon }}</div>
                </div>
            </div>
            <div class="card-base p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center"><XCircleIcon class="w-6 h-6" /></div>
                <div>
                    <div class="text-xs text-slate-500">เกินกำหนด</div>
                    <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ summary.overdue }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-base p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select v-model.number="filters.department_id" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option value="">ทุกหน่วยงาน</option>
                    <option v-for="d in master.departments" :key="d.id" :value="d.id">{{ d.code }} — {{ d.name_th }}</option>
                </select>
                <select v-model="filters.result" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option value="">ผลทั้งหมด</option>
                    <option value="PASS">ผ่าน</option>
                    <option value="FAIL">ไม่ผ่าน</option>
                </select>
                <select v-model.number="filters.per_page" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option :value="10">10/หน้า</option>
                    <option :value="25">25/หน้า</option>
                    <option :value="50">50/หน้า</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="card-base overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3 font-semibold">วันที่สอบ</th>
                            <th class="px-5 py-3 font-semibold">เครื่องมือ</th>
                            <th class="px-5 py-3 font-semibold">หน่วยงาน</th>
                            <th class="px-5 py-3 font-semibold">องค์กร</th>
                            <th class="px-5 py-3 font-semibold">เจ้าหน้าที่</th>
                            <th class="px-5 py-3 font-semibold">ผล</th>
                            <th class="px-5 py-3 font-semibold">ครบกำหนดถัดไป</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">กำลังโหลด...</td>
                        </tr>
                        <tr v-else-if="!items.length">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">ยังไม่มีรายการสอบเทียบ</td>
                        </tr>
                        <tr v-for="c in items" :key="c.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3 text-xs text-slate-700 whitespace-nowrap">
                                {{ new Date(c.calibrated_at).toLocaleDateString('th-TH') }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-mono text-xs text-blue-700">{{ c.equipment?.id_code }}</div>
                                <div class="text-xs text-slate-600 truncate max-w-[200px]">{{ c.equipment?.name_th }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700 font-medium">
                                    {{ c.equipment?.department?.code }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600 text-xs">{{ c.organization }}</td>
                            <td class="px-5 py-3 text-slate-600 text-xs">
                                <div>{{ c.calibrator_name || '—' }}</div>
                                <div class="text-[10px] text-slate-400">{{ c.calibrator_phone || '' }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <span :class="['inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full font-medium',
                                    c.result === 'PASS' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
                                    {{ c.result === 'PASS' ? '✓ ผ่าน' : '✗ ไม่ผ่าน' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap">
                                {{ c.next_due_at ? new Date(c.next_due_at).toLocaleDateString('th-TH') : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between px-5 py-4 border-t border-slate-100">
                <div class="text-xs text-slate-500">หน้า {{ meta.current_page }} / {{ meta.last_page }}</div>
                <div class="flex items-center gap-1">
                    <button :disabled="filters.page === 1" @click="filters.page--" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-40">
                        <ChevronLeftIcon class="w-4 h-4" />
                    </button>
                    <button v-for="p in pageRange" :key="p" @click="filters.page = p"
                        :class="['min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium', p === meta.current_page ? 'bg-blue-600 text-white' : 'border border-slate-200 hover:bg-slate-50']">
                        {{ p }}
                    </button>
                    <button :disabled="filters.page === meta.last_page" @click="filters.page++" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-40">
                        <ChevronRightIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <CalibrationCreateModal
            :open="showCreateModal"
            @close="showCreateModal = false"
            @created="onCreated"
        />
    </div>
</template>
