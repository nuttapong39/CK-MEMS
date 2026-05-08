<script setup>
import { onMounted, reactive, ref } from 'vue';
import {
    DocumentTextIcon, TableCellsIcon, DocumentArrowDownIcon,
    WrenchScrewdriverIcon, BeakerIcon, BookmarkSquareIcon,
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { reportsApi, downloadBlob } from '../../api/reports';
import { useMasterStore } from '../../stores/master';

const master = useMasterStore();

const equipmentFilter = reactive({
    department_id: '',
    status: '',
    fiscal_year: '',
});

const repairFilter = reactive({
    from: '',
    to: '',
    status: '',
});

const calibrationFilter = reactive({
    from: '',
    to: '',
});

const downloading = ref(null);

onMounted(() => master.loadAll());

async function run(key, fn, filename) {
    downloading.value = key;
    try {
        const blob = await fn();
        downloadBlob(blob, filename);
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ดาวน์โหลดไม่สำเร็จ', text: e.response?.data?.message || e.message || '' });
    } finally {
        downloading.value = null;
    }
}

const ts = () => new Date().toISOString().replace(/[:T]/g, '-').slice(0, 16);
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                <DocumentArrowDownIcon class="w-6 h-6" />
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800">รายงาน (Reports)</h1>
                <p class="text-xs text-slate-500 mt-0.5">ดาวน์โหลดข้อมูลในรูปแบบ PDF หรือ Excel</p>
            </div>
        </div>

        <!-- Equipments report -->
        <div class="card-base p-6 space-y-4">
            <div class="flex items-center gap-2">
                <BookmarkSquareIcon class="w-5 h-5 text-blue-600" />
                <h2 class="text-base font-semibold text-slate-800">รายการเครื่องมือทั้งหมด</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select v-model.number="equipmentFilter.department_id" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option value="">ทุกหน่วยงาน</option>
                    <option v-for="d in master.departments" :key="d.id" :value="d.id">{{ d.code }} — {{ d.name_th }}</option>
                </select>
                <select v-model="equipmentFilter.status" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="ACTIVE">ใช้งานอยู่</option>
                    <option value="BROKEN">ชำรุด</option>
                    <option value="UNDER_REPAIR">กำลังซ่อม</option>
                    <option value="RETIRED">จำหน่าย</option>
                    <option value="PENDING_DISPOSAL">รอแทงจำหน่าย</option>
                </select>
                <input v-model.number="equipmentFilter.fiscal_year" type="number" placeholder="ปีงบ (เช่น 2569)" class="px-3 py-2 rounded-xl border border-slate-200 text-sm" />
            </div>
            <div class="flex gap-2">
                <button
                    @click="run('eq-pdf', () => reportsApi.equipmentsPdf(equipmentFilter), `equipments_${ts()}.pdf`)"
                    :disabled="downloading === 'eq-pdf'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 transition disabled:opacity-50"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    {{ downloading === 'eq-pdf' ? 'กำลังสร้าง...' : 'PDF' }}
                </button>
                <button
                    @click="run('eq-xlsx', () => reportsApi.equipmentsExcel(equipmentFilter), `equipments_${ts()}.xlsx`)"
                    :disabled="downloading === 'eq-xlsx'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition disabled:opacity-50"
                >
                    <TableCellsIcon class="w-4 h-4" />
                    {{ downloading === 'eq-xlsx' ? 'กำลังสร้าง...' : 'Excel' }}
                </button>
            </div>
        </div>

        <!-- Repairs report -->
        <div class="card-base p-6 space-y-4">
            <div class="flex items-center gap-2">
                <WrenchScrewdriverIcon class="w-5 h-5 text-rose-600" />
                <h2 class="text-base font-semibold text-slate-800">ประวัติการแจ้งซ่อม</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="text-[10px] text-slate-400 uppercase tracking-wider">ตั้งแต่</label>
                    <input v-model="repairFilter.from" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                </div>
                <div>
                    <label class="text-[10px] text-slate-400 uppercase tracking-wider">ถึง</label>
                    <input v-model="repairFilter.to" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                </div>
                <div>
                    <label class="text-[10px] text-slate-400 uppercase tracking-wider">สถานะ</label>
                    <select v-model="repairFilter.status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                        <option value="">ทุกสถานะ</option>
                        <option value="PENDING">รอรับเรื่อง</option>
                        <option value="IN_PROGRESS">กำลังซ่อม</option>
                        <option value="REPAIRED">ซ่อมเสร็จ</option>
                        <option value="CLOSED">ปิดงาน</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    @click="run('rp-pdf', () => reportsApi.repairsPdf(repairFilter), `repairs_${ts()}.pdf`)"
                    :disabled="downloading === 'rp-pdf'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 disabled:opacity-50"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    {{ downloading === 'rp-pdf' ? 'กำลังสร้าง...' : 'PDF' }}
                </button>
                <button
                    @click="run('rp-xlsx', () => reportsApi.repairsExcel(repairFilter), `repairs_${ts()}.xlsx`)"
                    :disabled="downloading === 'rp-xlsx'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50"
                >
                    <TableCellsIcon class="w-4 h-4" />
                    {{ downloading === 'rp-xlsx' ? 'กำลังสร้าง...' : 'Excel' }}
                </button>
            </div>
        </div>

        <!-- Calibrations report -->
        <div class="card-base p-6 space-y-4">
            <div class="flex items-center gap-2">
                <BeakerIcon class="w-5 h-5 text-emerald-600" />
                <h2 class="text-base font-semibold text-slate-800">การสอบเทียบ</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] text-slate-400 uppercase tracking-wider">ตั้งแต่</label>
                    <input v-model="calibrationFilter.from" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                </div>
                <div>
                    <label class="text-[10px] text-slate-400 uppercase tracking-wider">ถึง</label>
                    <input v-model="calibrationFilter.to" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    @click="run('cal-xlsx', () => reportsApi.calibrationsExcel(calibrationFilter), `calibrations_${ts()}.xlsx`)"
                    :disabled="downloading === 'cal-xlsx'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50"
                >
                    <TableCellsIcon class="w-4 h-4" />
                    {{ downloading === 'cal-xlsx' ? 'กำลังสร้าง...' : 'Excel' }}
                </button>
                <span class="text-xs text-slate-400 self-center">ใบรับรองรายเครื่อง — กดปุ่มในหน้าสอบเทียบ</span>
            </div>
        </div>
    </div>
</template>
