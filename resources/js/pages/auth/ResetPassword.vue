<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeftIcon, LockClosedIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import client from '../../api/client';

const router = useRouter();

const step         = ref(1); // 1 = enter code, 2 = enter new password
const emergencyCode = ref('');
const newPassword   = ref('');
const confirmPwd    = ref('');
const showPwd       = ref(false);
const loading       = ref(false);

async function verifyCode() {
    if (!emergencyCode.value.trim()) return;
    loading.value = true;
    try {
        await client.post('/auth/emergency-reset/verify', { emergency_code: emergencyCode.value.trim() });
        step.value = 2;
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'รหัสฉุกเฉินไม่ถูกต้อง',
            text: e.response?.data?.message || 'กรุณาตรวจสอบรหัสที่ได้รับจากผู้ดูแลระบบ',
            confirmButtonColor: '#ef4444',
        });
    } finally {
        loading.value = false;
    }
}

async function doReset() {
    if (newPassword.value.length < 6) {
        Swal.fire({ icon: 'warning', title: 'รหัสผ่านสั้นเกินไป', text: 'ต้องมีอย่างน้อย 6 ตัวอักษร', confirmButtonColor: '#3b82f6' });
        return;
    }
    if (newPassword.value !== confirmPwd.value) {
        Swal.fire({ icon: 'warning', title: 'รหัสผ่านไม่ตรงกัน', confirmButtonColor: '#3b82f6' });
        return;
    }

    loading.value = true;
    try {
        await client.post('/auth/emergency-reset', {
            emergency_code: emergencyCode.value.trim(),
            new_password:   newPassword.value,
        });

        await Swal.fire({
            icon: 'success',
            title: 'รีเซ็ตรหัสผ่านสำเร็จ!',
            text: 'รหัสผ่านของ Admin ถูกเปลี่ยนแล้ว กรุณาเข้าสู่ระบบด้วยรหัสใหม่',
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'ไปหน้า Login',
        });
        router.push({ name: 'login' });
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'รีเซ็ตไม่สำเร็จ',
            text: e.response?.data?.message || 'เกิดข้อผิดพลาด',
            confirmButtonColor: '#ef4444',
        });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen relative overflow-hidden flex items-center justify-center p-6">

        <!-- Background -->
        <img src="/img/bg.jpg" alt="" class="absolute inset-0 w-full h-full object-cover pointer-events-none" />
        <div class="absolute inset-0 bg-slate-900/75 backdrop-blur-sm"></div>

        <!-- Card -->
        <div class="relative z-10 w-full max-w-md">
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl p-8">

                <!-- Back -->
                <button
                    @click="router.push({ name: 'login' })"
                    class="flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition"
                >
                    <ArrowLeftIcon class="w-4 h-4" />
                    กลับหน้าเข้าสู่ระบบ
                </button>

                <!-- Icon + Title -->
                <div class="flex flex-col items-center mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-rose-500/20 border border-rose-400/30 flex items-center justify-center mb-4">
                        <ShieldExclamationIcon class="w-7 h-7 text-rose-400" />
                    </div>
                    <h1 class="text-xl font-bold text-white">รีเซ็ตรหัสผ่านฉุกเฉิน</h1>
                    <p class="text-slate-400 text-sm mt-1 text-center">
                        สำหรับกรณีเข้าสู่ระบบไม่ได้<br>ต้องใช้รหัสฉุกเฉินจากผู้ดูแลระบบ
                    </p>
                </div>

                <!-- Step indicator -->
                <div class="flex items-center gap-2 mb-6">
                    <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold', step >= 1 ? 'bg-blue-600 text-white' : 'bg-white/10 text-slate-500']">1</div>
                    <div class="flex-1 h-0.5" :class="step >= 2 ? 'bg-blue-600' : 'bg-white/10'"></div>
                    <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold', step >= 2 ? 'bg-blue-600 text-white' : 'bg-white/10 text-slate-500']">2</div>
                </div>

                <!-- ── Step 1: Emergency Code ────────────────────── -->
                <div v-if="step === 1" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">
                            รหัสฉุกเฉิน (Emergency Code)
                        </label>
                        <input
                            v-model="emergencyCode"
                            type="password"
                            placeholder="กรอกรหัสฉุกเฉิน"
                            @keyup.enter="verifyCode"
                            class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-500 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 outline-none transition"
                        />
                        <p class="text-xs text-slate-500 mt-2">
                            รหัสฉุกเฉินอยู่ใน <code class="text-amber-300">.env</code> ที่ตัวแปร <code class="text-amber-300">EMERGENCY_RESET_CODE</code>
                        </p>
                    </div>

                    <button
                        @click="verifyCode"
                        :disabled="loading || !emergencyCode.trim()"
                        class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold transition disabled:opacity-50"
                    >
                        {{ loading ? 'กำลังตรวจสอบ...' : 'ยืนยันรหัสฉุกเฉิน' }}
                    </button>

                    <!-- Default code hint -->
                    <div class="bg-amber-500/10 border border-amber-400/20 rounded-xl p-3 text-xs text-slate-400">
                        <p class="font-medium text-amber-300 mb-1">หากยังไม่ได้ตั้งค่า</p>
                        <p>รหัสเริ่มต้น: <code class="text-amber-200 font-mono">CK-MEMS-RESET-2024</code></p>
                        <p class="mt-1">เปลี่ยนได้ที่ไฟล์ <code class="text-slate-300">.env</code> → <code class="text-amber-200">EMERGENCY_RESET_CODE</code></p>
                    </div>
                </div>

                <!-- ── Step 2: New Password ──────────────────────── -->
                <div v-else class="space-y-5">
                    <div class="bg-emerald-500/10 border border-emerald-400/20 rounded-xl px-4 py-3 text-sm text-emerald-300 flex items-center gap-2">
                        <LockClosedIcon class="w-4 h-4 shrink-0" />
                        ยืนยันรหัสฉุกเฉินสำเร็จ — กรอกรหัสผ่านใหม่
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">รหัสผ่านใหม่</label>
                        <div class="relative">
                            <input
                                v-model="newPassword"
                                :type="showPwd ? 'text' : 'password'"
                                placeholder="อย่างน้อย 6 ตัวอักษร"
                                class="w-full px-4 py-3 pr-12 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-500 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 outline-none transition"
                            />
                            <button type="button" @click="showPwd = !showPwd" class="absolute inset-y-0 right-3 text-slate-400 hover:text-white text-xs px-1">
                                {{ showPwd ? 'ซ่อน' : 'แสดง' }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">ยืนยันรหัสผ่านใหม่</label>
                        <input
                            v-model="confirmPwd"
                            :type="showPwd ? 'text' : 'password'"
                            placeholder="กรอกรหัสผ่านอีกครั้ง"
                            @keyup.enter="doReset"
                            class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-500 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 outline-none transition"
                            :class="confirmPwd && newPassword !== confirmPwd ? 'border-rose-400/60' : ''"
                        />
                        <p v-if="confirmPwd && newPassword !== confirmPwd" class="text-xs text-rose-400 mt-1">รหัสผ่านไม่ตรงกัน</p>
                    </div>

                    <button
                        @click="doReset"
                        :disabled="loading || !newPassword || newPassword !== confirmPwd"
                        class="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold transition disabled:opacity-50"
                    >
                        {{ loading ? 'กำลังรีเซ็ต...' : '🔐 รีเซ็ตรหัสผ่าน Admin' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>
