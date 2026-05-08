<script setup>
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';
import BaseModal from '../common/BaseModal.vue';
import { usersApi } from '../../api/users';
import { useMasterStore } from '../../stores/master';

const props = defineProps({
    open: { type: Boolean, default: false },
    editing: { type: Object, default: null },
});
const emit = defineEmits(['close', 'saved']);

const master = useMasterStore();

const form = ref({
    full_name: '',
    employee_code: '',
    email: '',
    phone: '',
    password: '',
    department_id: null,
    role: 'staff',
    is_active: true,
});
const errors = ref({});
const submitting = ref(false);

const isEdit = computed(() => !!props.editing);

watch(() => props.open, async (open) => {
    if (open) {
        await master.loadAll();
        if (props.editing) {
            form.value = {
                full_name: props.editing.full_name || props.editing.name || '',
                employee_code: props.editing.employee_code || '',
                email: props.editing.email,
                phone: props.editing.phone || '',
                password: '',
                department_id: props.editing.department?.id || null,
                role: (props.editing.roles?.[0]?.name || props.editing.roles?.[0] || 'staff'),
                is_active: !!props.editing.is_active,
            };
        } else {
            form.value = {
                full_name: '', employee_code: '', email: '', phone: '',
                password: '', department_id: null, role: 'staff', is_active: true,
            };
        }
        errors.value = {};
    }
});

async function submit() {
    submitting.value = true;
    errors.value = {};
    try {
        const payload = { ...form.value };
        if (isEdit.value && !payload.password) delete payload.password;
        if (isEdit.value) {
            await usersApi.update(props.editing.id, payload);
        } else {
            await usersApi.store(payload);
        }
        Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1200, showConfirmButton: false });
        emit('saved');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
        } else {
            Swal.fire({ icon: 'error', title: 'ล้มเหลว', text: e.response?.data?.message || '' });
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
        :title="isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่'"
        size="2xl"
    >
        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
                    <input v-model="form.full_name" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                    <p v-if="errors.full_name" class="text-xs text-rose-500 mt-0.5">{{ errors.full_name[0] }}</p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">รหัสพนักงาน</label>
                    <input v-model="form.employee_code" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">Email <span class="text-rose-500">*</span></label>
                    <input v-model="form.email" type="email" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                    <p v-if="errors.email" class="text-xs text-rose-500 mt-0.5">{{ errors.email[0] }}</p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">เบอร์โทร</label>
                    <input v-model="form.phone" type="tel" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">หน่วยงาน</label>
                    <select v-model.number="form.department_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:border-blue-500">
                        <option :value="null">— ไม่ระบุ —</option>
                        <option v-for="d in master.departments" :key="d.id" :value="d.id">{{ d.code }} — {{ d.name_th }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-700 block mb-1">สิทธิ์ <span class="text-rose-500">*</span></label>
                    <select v-model="form.role" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:border-blue-500">
                        <option value="admin">Admin (สิทธิ์เต็ม)</option>
                        <option value="staff">Staff (เครื่องมือ + ซ่อม + สอบเทียบ)</option>
                        <option value="user">User (แจ้งซ่อม + Dashboard)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-700 block mb-1">
                    รหัสผ่าน
                    <span v-if="!isEdit" class="text-rose-500">*</span>
                    <span v-else class="text-[10px] text-slate-400 ml-2">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</span>
                </label>
                <input v-model="form.password" type="password" :required="!isEdit" minlength="6"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500" />
                <p v-if="errors.password" class="text-xs text-rose-500 mt-0.5">{{ errors.password[0] }}</p>
            </div>

            <label class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 cursor-pointer">
                <input v-model="form.is_active" type="checkbox" class="w-4 h-4 accent-blue-600" />
                <span class="text-sm">เปิดใช้งานบัญชี</span>
            </label>
        </form>

        <template #footer>
            <button @click="emit('close')" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">ยกเลิก</button>
            <button @click="submit" :disabled="submitting"
                class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold disabled:opacity-50">
                {{ submitting ? 'กำลังบันทึก...' : (isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้') }}
            </button>
        </template>
    </BaseModal>
</template>
