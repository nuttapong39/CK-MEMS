import { defineStore } from 'pinia';
import client, { setUnauthorizedHandler } from '../api/client';
import router from '../router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        user: null,
        roles: [],
        permissions: [],
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.roles.includes('admin'),
        isStaff: (state) => state.roles.includes('staff'),
        isUser: (state) => state.roles.includes('user'),
        can: (state) => (perm) => state.permissions.includes(perm),
        hasRole: (state) => (role) => state.roles.includes(role),
        hasAnyRole: (state) => (list) => list.some((r) => state.roles.includes(r)),
        fullName: (state) => state.user?.full_name ?? state.user?.name ?? '',
        hospitalName: (state) => state.user?.hospital?.name_th ?? '',
    },

    actions: {
        hydrate() {
            const raw = localStorage.getItem('ckmems.session');
            if (!raw) return;
            try {
                const s = JSON.parse(raw);
                this.token = s.token;
                this.user = s.user;
                this.roles = s.roles ?? [];
                this.permissions = s.permissions ?? [];
            } catch (_) {
                localStorage.removeItem('ckmems.session');
            }
            setUnauthorizedHandler(() => this.handleUnauthorized());
        },

        persist() {
            localStorage.setItem('ckmems.token', this.token ?? '');
            localStorage.setItem(
                'ckmems.session',
                JSON.stringify({
                    token: this.token,
                    user: this.user,
                    roles: this.roles,
                    permissions: this.permissions,
                }),
            );
        },

        async login(username, password) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await client.post('/auth/login', { username, password });
                this.token = data.access_token;
                this.user = data.user;
                this.roles = data.roles;
                this.permissions = data.permissions;
                this.persist();
                setUnauthorizedHandler(() => this.handleUnauthorized());
                return true;
            } catch (e) {
                this.error = e.response?.data?.message
                    || e.response?.data?.errors?.username?.[0]
                    || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async fetchMe() {
            try {
                const { data } = await client.get('/auth/me');
                this.user = data.user;
                this.roles = data.roles;
                this.permissions = data.permissions;
                this.persist();
            } catch (_) {
                this.handleUnauthorized();
            }
        },

        async logout() {
            try { await client.post('/auth/logout'); } catch (_) {}
            this.handleUnauthorized();
        },

        handleUnauthorized() {
            this.token = null;
            this.user = null;
            this.roles = [];
            this.permissions = [];
            localStorage.removeItem('ckmems.token');
            localStorage.removeItem('ckmems.session');
            if (router.currentRoute.value.name !== 'login') {
                router.push({ name: 'login' });
            }
        },
    },
});
