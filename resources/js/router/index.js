import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/auth/Login.vue'),
        meta: { layout: 'auth', guestOnly: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: { name: 'dashboard' } },
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('../pages/Dashboard.vue'),
                meta: { title: 'หน้าแรก' },
            },
            {
                path: 'equipment',
                name: 'equipment.list',
                component: () => import('../pages/equipment/EquipmentList.vue'),
                meta: { title: 'เครื่องมือแพทย์', roles: ['admin', 'staff'] },
            },
            {
                path: 'equipment/create',
                name: 'equipment.create',
                component: () => import('../pages/equipment/EquipmentCreate.vue'),
                meta: { title: 'เพิ่มเครื่องมือแพทย์', roles: ['admin', 'staff'] },
            },
            {
                path: 'calibration',
                name: 'calibration.list',
                component: () => import('../pages/calibration/CalibrationList.vue'),
                meta: { title: 'การสอบเทียบ', roles: ['admin', 'staff'] },
            },
            {
                path: 'repair',
                name: 'repair.list',
                component: () => import('../pages/repair/RepairList.vue'),
                meta: { title: 'ประวัติการแจ้งซ่อม' },
            },
            {
                path: 'repair/create',
                name: 'repair.create',
                component: () => import('../pages/repair/RepairCreate.vue'),
                meta: { title: 'แจ้งซ่อม' },
            },
            {
                path: 'repair/:id',
                name: 'repair.detail',
                component: () => import('../pages/repair/RepairDetail.vue'),
                meta: { title: 'รายละเอียดการซ่อม' },
            },
            {
                path: 'qrcode',
                name: 'qrcode.designer',
                component: () => import('../pages/qrcode/QrDesigner.vue'),
                meta: { title: 'QR Code Designer', roles: ['admin'] },
            },
            {
                path: 'reports',
                name: 'reports',
                component: () => import('../pages/reports/ReportsIndex.vue'),
                meta: { title: 'รายงาน', roles: ['admin', 'staff'] },
            },
            {
                path: 'users',
                name: 'users.list',
                component: () => import('../pages/admin/UserManagement.vue'),
                meta: { title: 'จัดการผู้ใช้', roles: ['admin'] },
            },
            {
                path: 'moph/settings',
                name: 'moph.settings',
                component: () => import('../pages/moph/MophSettings.vue'),
                meta: { title: 'ตั้งค่า MOPH Alert', roles: ['admin'] },
            },
            {
                path: 'moph/flex',
                name: 'moph.flex',
                component: () => import('../pages/moph/FlexDesigner.vue'),
                meta: { title: 'Flex Designer', roles: ['admin'] },
            },
            {
                path: 'moph/logs',
                name: 'moph.logs',
                component: () => import('../pages/moph/NotificationLogs.vue'),
                meta: { title: 'Notification Logs', roles: ['admin'] },
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: { name: 'dashboard' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
    if (to.meta.roles && !auth.hasAnyRole(to.meta.roles)) {
        return { name: 'dashboard' };
    }
});

export default router;
