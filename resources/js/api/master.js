import client from './client';

export const masterApi = {
    departments: () => client.get('/master/departments'),
    equipmentCodes: () => client.get('/master/equipment-codes'),
    riskLevels: () => client.get('/master/risk-levels'),
    dashboardStats: () => client.get('/dashboard/stats'),
};
