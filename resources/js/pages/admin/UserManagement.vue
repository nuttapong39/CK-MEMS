<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
    PlusIcon, MagnifyingGlassIcon, PencilSquareIcon, TrashIcon,
    KeyIcon, CheckCircleIcon, XCircleIcon, ChevronLeftIcon, ChevronRightIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { usersApi }   from '../../api/users';
import { useMasterStore } from '../../stores/master';
import { useAuthStore }   from '../../stores/auth';
import UserFormModal         from '../../components/admin/UserFormModal.vue';
import ChangeCredentialsModal from '../../components/admin/ChangeCredentialsModal.vue';

const auth   = useAuthStore();
const master = useMasterStore();

const items   = ref([]);
const meta    = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 });
const loading = ref(false);
const filters = reactive({ search: '', role: '', is_active: '', page: 1, per_page: 25 });

// Modals
const showFormModal   = ref(false);
const editingUser     = ref(null);
const showCredentials = ref(false);
const credentialsUser = ref(null);

async function load() {
    loading.value = true;
    try {
        const params = {};
        for (const [k, v] of Object.entries(filters)) {
            if (v !== '' && v !== null) params[k] = v;
        }
        const { data } = await usersApi.list(params);
        items.value = data.data;
        meta.value  = data.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await master.loadAll();
    await load();
});

let debounceId = null;
watch(() => filters.search, () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => { filters.page = 1; load(); }, 300);
});
watch(() => [filters.role, filters.is_active, filters.per_page], () => { filters.page = 1; load(); });
watch(() => filters.page, load);

function openCreate() { editingUser.value = null; showFormModal.value = true; }
function openEdit(user) { editingUser.value = user; showFormModal.value = true; }
function onSaved() { showFormModal.value = false; load(); }

function openCredentials(user) {
    credentialsUser.value = user;
    showCredentials.value = true;
}
function onCredentialsSaved() { load(); }

async function deleteUser(user) {
    if (user.id === auth.user?.id) {
        Swal.fire({ icon: 'warning', title: 'ลบบัญชีตนเองไม่ได้' });
        return;
    }
    const r = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ?',
        text: `ลบผู้ใช้ ${user.full_name} (${user.email})?`,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก',
    });
    if (!r.isConfirmed) return;
    try {
        await usersApi.destroy(user.id);
        Swal.fire({ icon: 'success', title: 'ลบเรียบร้อย', timer: 1200, showConfirmButton: false });
        load();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ', text: e.response?.data?.message || '' });
    }
}

const roleBadge = (role) => ({
    admin: 'bg-rose-100 text-rose-700',
    staff: 'bg-blue-100 text-blue-700',
    user:  'bg-slate-100 text-slate-700',
}[role] ?? 'bg-slate-100 text-slate-600');

const pageRange = computed(() => {
    const cur = meta.value.current_page, last = meta.value.last_page;
    const start = Math.max(1, cur - 2), end = Math.min(last, cur + 2);
    const arr = [];
    for (let i = start; i <= end; i++) arr.push(i);
    return arr;
});
</script>

<template>
    <div class="space-y-4">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <UsersIcon class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">บริหารจัดการผู้ใช้งาน</h1>
                    <p class="text-xs text-slate-500 mt-0.5">ทั้งหมด {{ meta.total }} ผู้ใช้</p>
                </div>
            </div>
            <button
                @click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-orange-500 text-white font-semibold shadow-lg shadow-rose-500/30 hover:shadow-xl transition self-start text-sm"
            >
                <PlusIcon class="w-4 h-4" /> เพิ่มผู้ใช้
            </button>
        </div>

        <!-- Filters -->
        <div class="card-base p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative sm:col-span-2">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="filters.search" placeholder="ค้นหาชื่อ / email / รหัสพนักงาน"
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-blue-500 outline-none" />
                </div>
                <select v-model="filters.role" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option value="">ทุกสิทธิ์</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="user">User</option>
                </select>
                <select v-model="filters.is_active" class="px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
                    <option value="">ทั้งหมด</option>
                    <option value="1">เปิดใช้งาน</option>
                    <option value="0">ระงับ</option>
                </select>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="card-base overflow-hidden hidden sm:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3 font-semibold">ชื่อ-นามสกุล</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Username</th>
                            <th class="px-4 py-3 font-semibold hidden lg:table-cell">Email</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">หน่วยงาน</th>
                            <th class="px-4 py-3 font-semibold">สิทธิ์</th>
                            <th class="px-4 py-3 font-semibold hidden sm:table-cell">สถานะ</th>
                            <th class="px-4 py-3 font-semibold text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading"><td colspan="7" class="px-4 py-12 text-center text-slate-400">กำลังโหลด...</td></tr>
                        <tr v-else-if="!items.length"><td colspan="7" class="px-4 py-12 text-center text-slate-400">ไม่พบผู้ใช้</td></tr>
                        <tr v-for="u in items" :key="u.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ u.full_name || u.name }}</div>
                                <div class="text-xs text-slate-400">{{ u.phone || '' }}</div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="font-mono text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded-lg">{{ u.name }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 hidden lg:table-cell">{{ u.email }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 hidden md:table-cell">{{ u.department?.name_th || '—' }}</td>
                            <td class="px-4 py-3">
                                <template v-for="r in u.roles" :key="r.id || r">
                                    <span :class="['text-xs px-2 py-0.5 rounded-full font-semibold', roleBadge(r.name || r)]">
                                        {{ (r.name || r).toUpperCase() }}
                                    </span>
                                </template>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span v-if="u.is_active" class="inline-flex items-center gap-1 text-xs text-emerald-700">
                                    <CheckCircleIcon class="w-4 h-4" /> ใช้งาน
                                </span>
                                <span v-else class="inline-flex items-center gap-1 text-xs text-slate-400">
                                    <XCircleIcon class="w-4 h-4" /> ระงับ
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <button @click="openEdit(u)" class="p-1.5 rounded-lg hover:bg-amber-50 text-amber-600 transition" title="แก้ไขข้อมูล">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                    <button @click="openCredentials(u)" class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition" title="เปลี่ยน Username/Password">
                                        <KeyIcon class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="deleteUser(u)"
                                        :disabled="u.id === auth.user?.id"
                                        class="p-1.5 rounded-lg hover:bg-rose-50 text-rose-600 disabled:opacity-30 disabled:cursor-not-allowed transition"
                                        title="ลบ"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                <div class="text-xs text-slate-500">หน้า {{ meta.current_page }} / {{ meta.last_page }}</div>
                <div class="flex items-center gap-1">
                    <button :disabled="filters.page === 1" @click="filters.page--" class="p-2 rounded-lg border border-slate-200 disabled:opacity-40">
                        <ChevronLeftIcon class="w-4 h-4" />
                    </button>
                    <button v-for="p in pageRange" :key="p" @click="filters.page = p"
                        :class="['min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium', p === meta.current_page ? 'bg-blue-600 text-white' : 'border border-slate-200 hover:bg-slate-50']">
                        {{ p }}
                    </button>
                    <button :disabled="filters.page === meta.last_page" @click="filters.page++" class="p-2 rounded-lg border border-slate-200 disabled:opacity-40">
                        <ChevronRightIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
            <div v-if="loading" class="text-center py-10 text-slate-400 text-sm">กำลังโหลด...</div>
            <div v-else-if="!items.length" class="text-center py-10 text-slate-400 text-sm card-base p-6">ไม่พบผู้ใช้</div>
            <div v-for="u in items" :key="u.id" class="card-base p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-800 text-sm">{{ u.full_name || u.name }}</div>
                        <div class="font-mono text-xs text-blue-700 mt-0.5">@{{ u.name }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ u.email }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <template v-for="r in u.roles" :key="r.id || r">
                            <span :class="['text-xs px-2 py-0.5 rounded-full font-semibold', roleBadge(r.name || r)]">
                                {{ (r.name || r).toUpperCase() }}
                            </span>
                        </template>
                        <span v-if="u.is_active" class="text-xs text-emerald-600">● ใช้งาน</span>
                        <span v-else class="text-xs text-slate-400">● ระงับ</span>
                    </div>
                </div>
                <div class="flex gap-2 pt-1 border-t border-slate-100">
                    <button @click="openEdit(u)"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 transition">
                        <PencilSquareIcon class="w-4 h-4" /> แก้ไข
                    </button>
                    <button @click="openCredentials(u)"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                        <KeyIcon class="w-4 h-4" /> เปลี่ยนรหัส
                    </button>
                    <button v-if="u.id !== auth.user?.id" @click="deleteUser(u)"
                        class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 transition">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Mobile pagination -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 py-2">
                <button :disabled="filters.page === 1" @click="filters.page--"
                    class="px-3 py-2 rounded-lg border border-slate-200 text-sm disabled:opacity-40">‹ ก่อนหน้า</button>
                <span class="text-sm text-slate-500">{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button :disabled="filters.page === meta.last_page" @click="filters.page++"
                    class="px-3 py-2 rounded-lg border border-slate-200 text-sm disabled:opacity-40">ถัดไป ›</button>
            </div>
        </div>

        <!-- Modals -->
        <UserFormModal
            :open="showFormModal"
            :editing="editingUser"
            @close="showFormModal = false"
            @saved="onSaved"
        />
        <ChangeCredentialsModal
            :open="showCredentials"
            :user="credentialsUser"
            @close="showCredentials = false"
            @saved="onCredentialsSaved"
        />
    </div>
</template>
