<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { providerIdApi } from '../../api/users';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const email = ref('admin@ck-mems.local');
const password = ref('');
const showPassword = ref(false);

async function submit() {
    const ok = await auth.login(email.value, password.value);
    if (ok) {
        const redirect = route.query.redirect || { name: 'dashboard' };
        router.push(redirect);
    } else {
        Swal.fire({
            icon: 'error',
            title: 'เข้าสู่ระบบไม่สำเร็จ',
            text: auth.error || 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            confirmButtonColor: '#2563eb',
        });
    }
}

async function startProviderId() {
    try {
        const { data } = await providerIdApi.start();
        if (!data.configured) {
            Swal.fire({
                icon: 'info',
                title: 'Provider ID ยังไม่พร้อมใช้งาน',
                text: data.message || 'ระบบยังไม่ได้ตั้งค่า MOPH Provider ID',
                confirmButtonColor: '#2563eb',
            });
            return;
        }
        window.location.href = data.authorize_url;
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ไม่สามารถเริ่ม Provider ID', text: e.response?.data?.message || '' });
    }
}
</script>

<template>
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 p-8 sm:p-10">
            <!-- Brand -->
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl shadow-lg shadow-blue-500/30">
                    CK
                </div>
                <h1 class="text-2xl font-bold text-slate-800 mt-4">CK-MEMS</h1>
                <p class="text-sm text-slate-500 mt-1">ระบบบริหารจัดการเครื่องมือทางการแพทย์</p>
                <p class="text-xs text-slate-400 mt-0.5">โรงพยาบาลเชียงกลาง</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5">
                        อีเมล <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="example@ck-mems.local"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                    />
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5">
                        รหัสผ่าน <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition pr-20"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-3 text-xs text-slate-500 hover:text-blue-600"
                        >
                            {{ showPassword ? 'ซ่อน' : 'แสดง' }}
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="auth.loading"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    {{ auth.loading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
                </button>

                <div class="relative my-4">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                    <div class="relative flex justify-center"><span class="px-3 bg-white text-xs text-slate-400">หรือ</span></div>
                </div>

                <button
                    type="button"
                    @click="startProviderId"
                    class="w-full py-3 rounded-xl border-2 border-emerald-300 text-emerald-700 font-semibold hover:bg-emerald-50 transition flex items-center justify-center gap-2"
                >
                    <span>🪪</span>
                    เข้าใช้งานผ่าน MOPH Provider ID
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            CK-MEMS v0.1 · Powered by Laravel + Vue
        </p>
    </div>
</template>
