<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { repairsApi } from '../../api/repairs';
import { useAuthStore } from '../../stores/auth';
import StatusBadge from '../../components/repair/StatusBadge.vue';
import ProcessTimeline from '../../components/repair/ProcessTimeline.vue';
import { URGENCY_META, STATUS_META } from '../../composables/repairStatus';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const ticket = ref(null);
const loading = ref(true);

const canManage = computed(() => auth.hasAnyRole(['admin', 'staff']));

async function load() {
    loading.value = true;
    try {
        const { data } = await repairsApi.show(route.params.id);
        ticket.value = data;
    } finally {
        loading.value = false;
    }
}
onMounted(load);

async function transitionTo(toStatus) {
    const meta = STATUS_META[toStatus];
    let extra = {};
    if (toStatus === 'REPAIRED') {
        const { value: form } = await Swal.fire({
            title: 'บันทึกผลการซ่อม',
            html: `
                <textarea id="root_cause" class="swal2-textarea" placeholder="สาเหตุ"></textarea>
                <textarea id="action_taken" class="swal2-textarea" placeholder="วิธีการแก้ไข"></textarea>
                <input id="parts_used" class="swal2-input" placeholder="อะไหล่ที่ใช้" />
                <input id="repair_cost" class="swal2-input" type="number" placeholder="ค่าซ่อม (บาท)" />
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'บันทึกผลซ่อม',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#2563eb',
            preConfirm: () => ({
                root_cause: document.getElementById('root_cause').value,
                action_taken: document.getElementById('action_taken').value,
                parts_used: document.getElementById('parts_used').value,
                repair_cost: document.getElementById('repair_cost').value || null,
            }),
        });
        if (!form) return;
        extra = form;
    } else {
        const { value: note, isConfirmed } = await Swal.fire({
            title: 'เปลี่ยนสถานะ → ' + meta.label,
            input: 'textarea',
            inputLabel: 'หมายเหตุ (ไม่บังคับ)',
            inputPlaceholder: 'รายละเอียดเพิ่มเติม...',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#2563eb',
        });
        if (!isConfirmed) return;
        extra.note = note || null;
    }
    try {
        const { data } = await repairsApi.transition(ticket.value.id, { to_status: toStatus, ...extra });
        ticket.value = data;
        Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1200, showConfirmButton: false });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: e.response?.data?.message || '' });
    }
}
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-5">
        <div class="flex items-center gap-3">
            <button @click="router.back()" class="p-2 rounded-xl hover:bg-white hover:shadow-sm transition">
                <ArrowLeftIcon class="w-5 h-5 text-slate-600" />
            </button>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-800">{{ ticket?.ticket_no || 'กำลังโหลด...' }}</h1>
                    <StatusBadge v-if="ticket" :status="ticket.status" />
                </div>
                <p v-if="ticket" class="text-xs text-slate-500 mt-0.5">
                    แจ้งเมื่อ {{ new Date(ticket.reported_at).toLocaleString('th-TH') }}
                </p>
            </div>
        </div>

        <div v-if="loading" class="card-base p-12 text-center text-slate-400">กำลังโหลดรายละเอียด...</div>

        <template v-else-if="ticket">
            <!-- Workflow timeline -->
            <div class="card-base p-6">
                <ProcessTimeline :current-status="ticket.status" :logs="ticket.progress_logs || []" />
            </div>

            <!-- Detail sections -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="card-base p-5">
                    <div class="text-xs uppercase text-slate-400 tracking-wider mb-3 font-semibold">เครื่องมือ</div>
                    <div class="space-y-1.5">
                        <div class="text-sm font-mono text-blue-700">{{ ticket.equipment?.id_code }}</div>
                        <div class="font-medium text-slate-800">{{ ticket.equipment?.name_th }}</div>
                        <div class="text-xs text-slate-500">
                            {{ ticket.equipment?.manufacturer }} · {{ ticket.equipment?.model }}
                        </div>
                        <div class="text-xs text-slate-500">
                            หน่วยงาน: {{ ticket.equipment?.department?.name_th }}
                        </div>
                    </div>
                </div>

                <div class="card-base p-5">
                    <div class="text-xs uppercase text-slate-400 tracking-wider mb-3 font-semibold">รายละเอียดการแจ้ง</div>
                    <div class="space-y-2.5">
                        <div>
                            <div class="text-[10px] uppercase text-slate-400">ผู้แจ้ง</div>
                            <div class="text-sm">{{ ticket.reporter?.full_name }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-slate-400">ความเร่งด่วน</div>
                            <span :class="['inline-flex items-center gap-1 mt-1 text-xs px-2 py-1 rounded-full text-white', URGENCY_META[ticket.urgency]?.bg]">
                                {{ URGENCY_META[ticket.urgency]?.label }} · {{ URGENCY_META[ticket.urgency]?.sla }}
                            </span>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-slate-400">อาการ</div>
                            <div class="text-sm whitespace-pre-line">{{ ticket.symptom }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Repair completion details -->
            <div v-if="ticket.action_taken || ticket.root_cause" class="card-base p-5">
                <div class="text-xs uppercase text-slate-400 tracking-wider mb-3 font-semibold">ผลการซ่อม</div>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div v-if="ticket.root_cause"><dt class="text-xs text-slate-500">สาเหตุ</dt><dd>{{ ticket.root_cause }}</dd></div>
                    <div v-if="ticket.action_taken"><dt class="text-xs text-slate-500">วิธีแก้ไข</dt><dd>{{ ticket.action_taken }}</dd></div>
                    <div v-if="ticket.parts_used"><dt class="text-xs text-slate-500">อะไหล่</dt><dd>{{ ticket.parts_used }}</dd></div>
                    <div v-if="ticket.repair_cost"><dt class="text-xs text-slate-500">ค่าซ่อม</dt><dd>{{ Number(ticket.repair_cost).toLocaleString() }} บาท</dd></div>
                </dl>
            </div>

            <!-- Transition actions -->
            <div v-if="canManage && ticket.next_statuses?.length" class="card-base p-5">
                <div class="text-xs uppercase text-slate-400 tracking-wider mb-3 font-semibold">การดำเนินการ</div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="s in ticket.next_statuses"
                        :key="s"
                        @click="transitionTo(s)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition"
                    >
                        {{ STATUS_META[s].icon }} {{ STATUS_META[s].label }}
                    </button>
                </div>
            </div>

            <div v-else-if="canManage" class="card-base p-5 text-center text-sm text-slate-400">
                ไม่มี action เพิ่มเติม (สถานะปลายทาง)
            </div>
        </template>
    </div>
</template>
