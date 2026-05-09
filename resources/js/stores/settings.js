import { defineStore } from 'pinia';
import client from '../api/client';

export const useSettingsStore = defineStore('settings', {
    state: () => ({
        systemName: 'CK-MEMS',
        logoUrl: null,
        theme: localStorage.getItem('ck-theme') || 'light',
        loaded: false,
    }),

    actions: {
        /** Apply theme to <html> data-theme + persist to localStorage */
        applyTheme(theme) {
            const t = ['light', 'dark', 'pastel', 'classic'].includes(theme) ? theme : 'light';
            this.theme = t;
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('ck-theme', t);
        },

        /** Called before auth — uses public endpoint */
        async fetchPublic() {
            try {
                const { data } = await client.get('/system/public');
                this.systemName = data.system_name || 'CK-MEMS';
                this.logoUrl    = data.logo_url    || null;
                this.applyTheme(data.theme || 'light');
            } catch { /* keep default */ } finally {
                this.loaded = true;
            }
        },

        /** Called after auth — uses protected endpoint */
        async fetchAuthed() {
            try {
                const { data } = await client.get('/system/settings');
                this.systemName = data.system_name || 'CK-MEMS';
                this.logoUrl    = data.logo_url    || null;
                this.applyTheme(data.theme || 'light');
                this.loaded     = true;
            } catch { /* keep current */ }
        },

        /** Instant update after saving in SystemSettings */
        update(systemName, logoUrl = undefined, theme = undefined) {
            if (systemName !== undefined) this.systemName = systemName || 'CK-MEMS';
            if (logoUrl    !== undefined) this.logoUrl    = logoUrl;
            if (theme      !== undefined) this.applyTheme(theme);
        },
    },
});
