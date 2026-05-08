import { defineStore } from 'pinia';
import { masterApi } from '../api/master';

export const useMasterStore = defineStore('master', {
    state: () => ({
        departments: [],
        equipmentCodes: [],
        riskLevels: [],
        loaded: false,
        loading: false,
    }),

    getters: {
        departmentByCode: (state) => (code) => state.departments.find((d) => d.code === code),
        equipmentCodeByCode: (state) => (code) => state.equipmentCodes.find((c) => c.code === code),
    },

    actions: {
        async loadAll(force = false) {
            if (this.loaded && !force) return;
            this.loading = true;
            try {
                const [d, c, r] = await Promise.all([
                    masterApi.departments(),
                    masterApi.equipmentCodes(),
                    masterApi.riskLevels(),
                ]);
                this.departments = d.data;
                this.equipmentCodes = c.data;
                this.riskLevels = r.data;
                this.loaded = true;
            } finally {
                this.loading = false;
            }
        },
    },
});
