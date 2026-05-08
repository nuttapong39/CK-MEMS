<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { ArrowLeftIcon, CalendarIcon, MapPinIcon, ClockIcon, CheckIcon } from '@heroicons/vue/24/outline';
import UrgencyPicker from '../../components/repair/UrgencyPicker.vue';
import { useMasterStore } from '../../stores/master';
import { equipmentsApi } from '../../api/equipments';
import { repairsApi } from '../../api/repairs';

const router = useRouter();
const master = useMasterStore();

const submitting = ref(false);
const departmentId = ref(null);
const equipments = ref([]);
const equipmentsLoading = ref(false);

const form = ref({
    equipment_id: null,
    reported_at: new Date().toISOString().slice(0, 16),
    symptom: '',
    urgency: 'MEDIUM',
});

const errors = ref({});

const selectedEquipment = computed(() =>
    equipments.value.find((e) => e.id === form.value.equipment_id),
);

const locationLabel = computed(() => {
    const eq = selectedEquipment.value;
    if (!eq) return '— เลือกเครื่องมือก่อน —';
    return eq.location?.name
        || `${eq.department?.name_th ?? '—'} · ${eq.id_code}`;
});

onMounted(() => master.loadAll());

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
            status: 'ACTIVE',
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
        const payload = {
            equipment_id: form.value.equipment_id,
            reported_at: form.value.reported_at.replace('T', ' ') + ':00',
            symptom: form.value.symptom,
            urgency: form.value.urgency,
        };
        const { data } = await repairsApi.store(payload);
        await Swal.fire({
            icon: 'success',
            title: 'แจ้งซ่อมเรียบร้อย',
            text: `หมายเลข ${data.ticket_no}`,
            confirmButtonColor: '#2563eb',
        });
        router.push({ name: 'repair.list' });
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
            Swal.fire({ icon: 'warning', title: 'กรุณาตรวจสอบข้อมูล', confirmButtonColor: '#2563eb' });
        } else {
            Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: e.response?.data?.message || '' });
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <button @click="router.back()" class="p-2 rounded-xl hover:bg-white hover:shadow-sm transition">
                <ArrowLeftIcon class="w-5 h-5 text-slate-600" />
            </button>
            <div>
                <h1 class="text-xl font-bold text-slate-800">แจ้งซ่อมเครื่องมือแพทย์</h1>
                <p class="text-xs text-slate-500 mt-0.5">เลือกหน่วยงาน → เครื่องมือ → กรอกอาการ + ระดับความเร่งด่วน</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="card-base p-6 space-y-6">
            <!-- Cascading: dept -> equipment -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5">
                        หน่วยงานที่ตั้งเครื่อง <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model.number="departmentId"
                        required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white"
                    >
                        <option :value="null" disabled>-- เลือกหน่วยงาน --</option>
                        <option v-for="d in master.departments" :key="d.id" :value="d.id">
                            {{ d.code }} — {{ d.name_th }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5">
                        เครื่องมือที่ต้องการแจ้งซ่อม <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model.number="form.equipment_id"
                        required
                        :disabled="!departmentId || equipmentsLoading"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition bg-white disabled:bg-slate-50 disabled:text-slate-400"
                    >
                        <option :value="null" disabled>
                            {{ equipmentsLoading ? 'กำลังโหลด...' : !departmentId ? 'เลือกหน่วยงานก่อน' : 'เลือกเครื่องมือ' }}
                        </option>
                        <option v-for="e in equipments" :key="e.id" :value="e.id">
                            {{ e.id_code }} — {{ e.name_th }}
                        </option>
                    </select>
                    <p v-if="errors.equipment_id" class="text-xs text-rose-500 mt-1">{{ errors.equipment_id[0] }}</p>
                </div>
            </div>

            <!-- Auto-filled location -->
            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1.5 flex items-center gap-2">
                    <MapPinIcon class="w-4 h-4 text-blue-500" />
                    สถานที่ติดตั้ง (ดึงจากเครื่องมือ)
                </label>
                <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-700">
                    {{ locationLabel }}
                </div>
            </div>

            <!-- Reported at + symptom -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5 flex items-center gap-2">
                        <CalendarIcon class="w-4 h-4 text-blue-500" />
                        วันที่ที่แจ้งซ่อม <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.reported_at"
                        type="datetime-local"
                        required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                    />
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1.5">
                    อาการที่พบเจอ <span class="text-rose-500">*</span>
                </label>
                <textarea
                    v-model="form.symptom"
                    required
                    rows="4"
                    placeholder="เช่น เครื่องไม่ติด ไฟแสดงสถานะกระพริบสีแดง..."
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition resize-none"
                ></textarea>
                <p v-if="errors.symptom" class="text-xs text-rose-500 mt-1">{{ errors.symptom[0] }}</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700 block mb-2 flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-rose-500" />
                    ระดับความเร่งด่วน <span class="text-rose-500">*</span>
                </label>
                <UrgencyPicker v-model="form.urgency" />
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" @click="router.back()" class="px-5 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-slate-100 transition">
                    ยกเลิก
                </button>
                <button
                    type="submit"
                    :disabled="submitting || !form.equipment_id"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-orange-500 text-white font-semibold shadow-lg shadow-rose-500/30 hover:shadow-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <CheckIcon class="w-5 h-5" />
                    {{ submitting ? 'กำลังบันทึก...' : 'แจ้งซ่อม' }}
                </button>
            </div>
        </form>
    </div>
</template>
