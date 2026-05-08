<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { ArrowLeftIcon, EyeIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { mophApi } from '../../api/moph';
import FlexBubblePreview from '../../components/moph/FlexBubblePreview.vue';

const templates = ref([]);
const selected = ref(null);
const editor = ref({ name: '', alt_text: '', json_payload: '', is_active: true });
const loading = ref(true);
const saving = ref(false);
const previewing = ref(false);
const preview = ref(null);

async function loadList() {
    loading.value = true;
    try {
        const { data } = await mophApi.templates();
        templates.value = data;
        if (data.length && !selected.value) selectTemplate(data[0]);
    } finally {
        loading.value = false;
    }
}

async function selectTemplate(t) {
    const { data } = await mophApi.showTemplate(t.id);
    selected.value = data;
    editor.value = {
        name: data.name,
        alt_text: data.alt_text,
        json_payload: typeof data.json_payload === 'string'
            ? formatJson(data.json_payload)
            : JSON.stringify(data.json_payload, null, 2),
        is_active: data.is_active,
    };
    preview.value = null;
}

function formatJson(s) {
    try { return JSON.stringify(JSON.parse(s), null, 2); }
    catch { return s; }
}

async function save() {
    if (!selected.value) return;
    saving.value = true;
    try {
        await mophApi.updateTemplate(selected.value.id, editor.value);
        Swal.fire({ icon: 'success', title: 'บันทึก template เรียบร้อย', timer: 1200, showConfirmButton: false });
        loadList();
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'บันทึกไม่สำเร็จ',
            text: e.response?.data?.errors?.json_payload?.[0] || e.response?.data?.message || '',
        });
    } finally {
        saving.value = false;
    }
}

async function doPreview() {
    previewing.value = true;
    try {
        const { data } = await mophApi.previewTemplate({ json_payload: editor.value.json_payload });
        preview.value = data.rendered;
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Preview ไม่สำเร็จ',
            text: e.response?.data?.errors?.json_payload?.[0] || e.response?.data?.message || '',
        });
    } finally {
        previewing.value = false;
    }
}

function formatNow() {
    editor.value.json_payload = formatJson(editor.value.json_payload);
}

onMounted(loadList);

const previewBubble = computed(() => preview.value);
</script>

<template>
    <div class="space-y-5">
        <div class="flex items-center gap-3">
            <RouterLink :to="{ name: 'moph.settings' }" class="p-2 rounded-xl hover:bg-white hover:shadow-sm transition">
                <ArrowLeftIcon class="w-5 h-5 text-slate-600" />
            </RouterLink>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Flex Message Designer</h1>
                <p class="text-xs text-slate-500 mt-0.5">7 templates สำหรับเหตุการณ์ต่างๆ — แก้ JSON แล้วกด Preview</p>
            </div>
        </div>

        <div v-if="loading" class="card-base p-12 text-center text-slate-400">กำลังโหลด...</div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Template list -->
            <div class="lg:col-span-3 card-base p-3 self-start">
                <div class="text-[11px] uppercase font-semibold text-slate-400 px-2 py-2">เทมเพลต</div>
                <div class="space-y-1">
                    <button
                        v-for="t in templates"
                        :key="t.id"
                        @click="selectTemplate(t)"
                        :class="[
                            'w-full px-3 py-2.5 rounded-xl text-left text-sm transition flex items-start gap-2',
                            selected?.id === t.id ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-slate-50 text-slate-600',
                        ]"
                    >
                        <span class="text-base shrink-0">{{ {
                            CREATE_EQUIPMENT: '🩺', REPAIR_REQUEST: '🔧',
                            REPAIR_ACKNOWLEDGED: '📋', REPAIR_IN_PROGRESS: '⚙️',
                            REPAIR_COMPLETED: '✅', CALIBRATION_DONE: '🧪',
                            CALIBRATION_DUE: '⏳',
                        }[t.key] || '📨' }}</span>
                        <div class="min-w-0">
                            <div class="truncate">{{ t.name }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ t.key }}</div>
                        </div>
                        <CheckCircleIcon v-if="t.is_active" class="w-4 h-4 text-emerald-500 shrink-0 ml-auto" />
                    </button>
                </div>
            </div>

            <!-- Editor -->
            <div v-if="selected" class="lg:col-span-5 card-base p-5 space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">ชื่อเทมเพลต</label>
                    <input v-model="editor.name" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">Alt Text (สำรองเมื่อ Flex แสดงไม่ได้)</label>
                    <input v-model="editor.alt_text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-medium text-slate-700">JSON Payload</label>
                        <button @click="formatNow" class="text-[11px] text-blue-600 hover:underline">format JSON</button>
                    </div>
                    <textarea
                        v-model="editor.json_payload"
                        rows="20"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:border-blue-500 resize-y"
                    ></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">
                        ใช้ตัวแปร <code v-pre>{{ equipment.id_code }}</code>,
                        <code v-pre>{{ ticket.symptom }}</code>,
                        <code v-pre>{{ calibration.next_due_at }}</code> ฯลฯ
                    </p>
                </div>
                <label class="flex items-center gap-2">
                    <input v-model="editor.is_active" type="checkbox" class="w-4 h-4 accent-blue-600" />
                    <span class="text-sm text-slate-700">เปิดใช้งาน template นี้</span>
                </label>

                <div class="flex gap-2 pt-2 border-t border-slate-100">
                    <button @click="doPreview" :disabled="previewing"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-blue-200 text-blue-700 hover:bg-blue-50 text-sm font-medium disabled:opacity-50">
                        <EyeIcon class="w-4 h-4" />
                        {{ previewing ? 'กำลังแสดง...' : 'Preview' }}
                    </button>
                    <button @click="save" :disabled="saving"
                        class="ml-auto inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold disabled:opacity-50">
                        {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
                    </button>
                </div>
            </div>

            <!-- Preview -->
            <div class="lg:col-span-4 space-y-3">
                <div class="text-[11px] uppercase font-semibold text-slate-400 px-2">PREVIEW (ตัวแปรตัวอย่าง)</div>
                <div v-if="!preview" class="card-base p-8 text-center text-sm text-slate-400">
                    กดปุ่ม "Preview" เพื่อแสดงตัวอย่าง Bubble
                </div>
                <div v-else class="bg-[#7B95B7] p-5 rounded-2xl">
                    <FlexBubblePreview :payload="previewBubble" />
                </div>
            </div>
        </div>
    </div>
</template>
