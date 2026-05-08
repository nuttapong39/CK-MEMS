<script setup>
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';
import BaseModal from '../common/BaseModal.vue';
import { useMasterStore } from '../../stores/master';
import { equipmentsApi } from '../../api/equipments';
import { calibrationsApi } from '../../api/calibrations';
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ open: { type: Boolean, default: false } });
const emit = defineEmits(['close', 'created']);

const master = useMasterStore();

const departmentId = ref(null);
const equipments = ref([]);
const equipmentsLoading = ref(false);
const submitting = ref(false);
const form = ref({
    equipment_id: null,
    calibrated_at: new Date().toISOString().slice(0, 10),
    next_due_at: '',
    organization: '',
    calibrator_name: '',
    calibrator_phone: '',
    controller_name: '',
    result: 'PASS',
    certificate_no: '',
    cost: '',
    remark: '',
});
const errors = ref({});

watch(() => props.open, async (open) => {
    if (open) {
        await master.loadAll();
        Object.assign(form.value, {
            equipment_id: null,
            calibrated_at: new Date().toISOString().slice(0, 10),
            next_due_at: '',
            organization: '',
            calibrator_name: '',
            calibrator_phone: '',
            controller_name: '',
            result: 'PASS',
            certificate_no: '',
            cost: '',
            remark: '',
        });
        departmentId.value = null;
        equipments.value = [];
        errors.value = {};
    }
});

async function loadEquipments() {
    if (!departmentId.value) {
        equipments.value = [];
        return;
    }
    equipmentsLoading.value = true;
    try {
        const { data } = await equipmentsApi.list({
            department_id: departmentId.value,
            per_page: 100,
        });
        equipments.value = data.data;
    } finally {
        equipmentsLoading.value = false;
    }
}
watch(departmentId, () => {
    form.value.equipment_id = null;
    loadEquipments();
});

async function submit() {
    submitting.value = true;
    errors.value = {};
    try {
        const payload = { ...form.value };
        if (!payload.next_due_at) delete payload.next_due_at;
        if (!payload.cost) delete payload.cost;
        const { data } = await calibrationsApi.store(payload);
        Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', timer: 1200, showConfirmButton: false });
        emit('created', data);
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
        } else {
            Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: e.response?.data?.message || '' });
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <BaseModal
        :open="open"
        @close="emit('close')"
        title="เพิ่มการสอบเทียบ"
        subtitle="เลือกเครื่องมือ → กรอกข้อมูลเจ้าหน้าที่ + ผลสอบ"
        size="3xl"
    >
        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">หน่วยงาน <span class="text-rose-500">*</span></label>
                    <select v-model.number="departmentId" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:border-blue-500">
                        <option :value="null" disabled>-- เลือก --</option>
                        <option v-for="d in master.departments" :key="d.id" :value="d.id">{{ d.code }} — {{ d.name_th }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">เครื่องมือ <span class="text-rose-500">*</span></label>
                    <select v-model.number="form.equipment_id" required :disabled="!departmentId || equipmentsLoading"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:border-blue-500 disabled:bg-slate-50">
                        <option :value="null" disabled>{{ equipmentsLoading ? 'กำลังโหลด...' : 'เลือก' }}</option>
                        <option v-for="e in equipments" :key="e.id" :value="e.id">{{ e.id_code }} — {{ e.name_th }}</option>
                    </select>
                    <p v-if="errors.equipment_id" class="text-xs text-rose-500 mt-0.5">{{ errors.equipment_id[0] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">วันที่สอบเทียบ <span class="text-rose-500">*</span></label>
                    <input v-model="form.calibrated_at" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">ครบกำหนดสอบเทียบครั้งถัดไป (ถ้าเว้น = คำนวณอัตโนมัติ)</label>
                    <input v-model="form.next_due_at" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-700 block mb-1">องค์กรสอบเทียบ <span class="text-rose-500">*</span></label>
                <input v-model="form.organization" type="text" required placeholder="เช่น ศูนย์ วศ. / บริษัท ABC จำกัด" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                <p v-if="errors.organization" class="text-xs text-rose-500 mt-0.5">{{ errors.organization[0] }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">ชื่อเจ้าหน้าที่ผู้สอบเทียบ</label>
                    <input v-model="form.calibrator_name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">เบอร์โทรติดต่อ</label>
                    <input v-model="form.calibrator_phone" type="tel" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-700 block mb-1">เจ้าหน้าที่ผู้ควบคุม (ฝั่งโรงพยาบาล)</label>
                <input v-model="form.controller_name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
            </div>

            <div>
                <label class="text-xs font-medium text-slate-700 block mb-2">ผลการสอบเทียบ <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition',
                        form.result === 'PASS' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 font-semibold' : 'border-slate-200 hover:border-emerald-300']">
                        <input type="radio" v-model="form.result" value="PASS" class="sr-only" />
                        <CheckIcon class="w-5 h-5" /> ผ่าน
                    </label>
                    <label :class="['flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 cursor-pointer transition',
                        form.result === 'FAIL' ? 'border-rose-500 bg-rose-50 text-rose-700 font-semibold' : 'border-slate-200 hover:border-rose-300']">
                        <input type="radio" v-model="form.result" value="FAIL" class="sr-only" />
                        <XMarkIcon class="w-5 h-5" /> ไม่ผ่าน
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">เลขที่ใบรับรอง</label>
                    <input v-model="form.certificate_no" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">ค่าใช้จ่าย (บาท)</label>
                    <input v-model="form.cost" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-700 block mb-1">หมายเหตุ</label>
                <textarea v-model="form.remark" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500 resize-none"></textarea>
            </div>
        </form>

        <template #footer>
            <button @click="emit('close')" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">ยกเลิก</button>
            <button @click="submit" :disabled="submitting || !form.equipment_id"
                class="px-5 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-medium shadow disabled:opacity-50 disabled:cursor-not-allowed">
                {{ submitting ? 'กำลังบันทึก...' : 'บันทึกการสอบเทียบ' }}
            </button>
        </template>
    </BaseModal>
</template>
