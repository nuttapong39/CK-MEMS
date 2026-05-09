<script setup>
import { ref, watch, computed } from 'vue';
import BaseModal    from '../common/BaseModal.vue';
import { usersApi } from '../../api/users';
import { KeyIcon, UserCircleIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object,  default: null },
});
const emit = defineEmits(['close', 'saved']);

const submitting   = ref(false);
const showPass     = ref(false);
const showConfirm  = ref(false);

const form = ref({
    name:             '',
    password:         '',
    password_confirm: '',
});
const errors = ref({});

watch(() => props.open, (val) => {
    if (val && props.user) {
        form.value  = { name: props.user.name ?? '', password: '', password_confirm: '' };
        errors.value = {};
        showPass.value = false;
        showConfirm.value = false;
    }
});

const passwordMismatch = computed(() =>
    form.value.password && form.value.password_confirm &&
    form.value.password !== form.value.password_confirm,
);

const canSubmit = computed(() =>
    form.value.name.trim().length > 0 &&
    !passwordMismatch.value &&
    (!form.value.password || form.value.password.length >= 6),
);

async function submit() {
    errors.value = {};
    if (!canSubmit.value) return;

    if (form.value.password && form.value.password !== form.value.password_confirm) {
        errors.value.password_confirm = ['รหัสผ่านไม่ตรงกัน'];
        return;
    }

    submitting.value = true;
    try {
        const payload = { name: form.value.name.trim() };
        if (form.value.password) payload.password = form.value.password;

        await usersApi.update(props.user.id, payload);

        Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', text: 'อัพเดต Username / Password แล้ว', timer: 1800, showConfirmButton: false });
        emit('saved');
        emit('close');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {};
        } else {
            Swal.fire({ icon: 'error', title: 'ล้มเหลว', text: e.response?.data?.message ?? '' });
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <BaseModal
        :open="open"
        title="เปลี่ยน Username / Password"
        :subtitle="user ? `${user.full_name ?? user.name}` : ''"
        size="md"
        @close="emit('close')"
    >
        <div v-if="user" class="space-y-5">

            <!-- User info badge -->
            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center shrink-0">
                    <UserCircleIcon class="w-6 h-6 text-blue-500" />
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800 truncate">{{ user.full_name ?? user.name }}</div>
                    <div class="text-xs text-slate-400">Username ปัจจุบัน: <span class="font-mono text-blue-600">{{ user.name }}</span></div>
                </div>
            </div>

            <!-- Username field -->
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">
                    <span class="flex items-center gap-1">
                        <UserCircleIcon class="w-3.5 h-3.5" />
                        Username ใหม่ <span class="text-rose-500">*</span>
                    </span>
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    autocomplete="off"
                    placeholder="กรอก Username สำหรับ Login"
                    :class="['w-full px-3 py-2.5 rounded-xl border text-sm outline-none transition',
                        errors.name ? 'border-rose-400 focus:ring-2 focus:ring-rose-100' : 'border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100']"
                />
                <p v-if="errors.name" class="text-xs text-rose-500 mt-1">{{ errors.name[0] }}</p>
                <p class="text-[11px] text-slate-400 mt-1">ใช้สำหรับ Login เข้าระบบ (ตัวอักษร ตัวเลข ขีดล่าง)</p>
            </div>

            <div class="border-t border-slate-100"></div>

            <!-- New Password -->
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">
                    <span class="flex items-center gap-1">
                        <KeyIcon class="w-3.5 h-3.5" />
                        รหัสผ่านใหม่
                        <span class="text-[10px] text-slate-400 font-normal ml-1">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</span>
                    </span>
                </label>
                <div class="relative">
                    <input
                        v-model="form.password"
                        :type="showPass ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        minlength="6"
                        :class="['w-full px-3 py-2.5 pr-10 rounded-xl border text-sm outline-none transition',
                            errors.password ? 'border-rose-400' : 'border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100']"
                    />
                    <button type="button" @click="showPass = !showPass"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600">
                        <EyeSlashIcon v-if="showPass" class="w-4 h-4" />
                        <EyeIcon v-else class="w-4 h-4" />
                    </button>
                </div>
                <p v-if="errors.password" class="text-xs text-rose-500 mt-1">{{ errors.password[0] }}</p>
                <p class="text-[11px] text-slate-400 mt-1">อย่างน้อย 6 ตัวอักษร</p>
            </div>

            <!-- Confirm Password -->
            <div v-if="form.password">
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">ยืนยันรหัสผ่านใหม่ <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <input
                        v-model="form.password_confirm"
                        :type="showConfirm ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :class="['w-full px-3 py-2.5 pr-10 rounded-xl border text-sm outline-none transition',
                            passwordMismatch ? 'border-rose-400 focus:ring-2 focus:ring-rose-100' : 'border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100']"
                    />
                    <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600">
                        <EyeSlashIcon v-if="showConfirm" class="w-4 h-4" />
                        <EyeIcon v-else class="w-4 h-4" />
                    </button>
                </div>
                <p v-if="passwordMismatch" class="text-xs text-rose-500 mt-1">รหัสผ่านไม่ตรงกัน</p>
                <p v-else-if="form.password_confirm && !passwordMismatch" class="text-xs text-emerald-600 mt-1">✓ รหัสผ่านตรงกัน</p>
            </div>
        </div>

        <template #footer>
            <button @click="emit('close')" class="px-4 py-2 rounded-xl text-sm text-slate-600 hover:bg-slate-100 transition">ยกเลิก</button>
            <button
                @click="submit"
                :disabled="submitting || !canSubmit"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <KeyIcon v-else class="w-4 h-4" />
                {{ submitting ? 'กำลังบันทึก...' : 'บันทึก' }}
            </button>
        </template>
    </BaseModal>
</template>
