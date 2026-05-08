import client from './client';

export const usersApi = {
    list: (params = {}) => client.get('/users', { params }),
    show: (id) => client.get(`/users/${id}`),
    store: (data) => client.post('/users', data),
    update: (id, data) => client.put(`/users/${id}`, data),
    destroy: (id) => client.delete(`/users/${id}`),
    resetPassword: (id, password) => client.post(`/users/${id}/reset-password`, { password }),
    roles: () => client.get('/users/roles'),
};

export const providerIdApi = {
    start: () => client.get('/auth/provider-id/start'),
    demoExchange: (provider_id) => client.post('/auth/provider-id/demo-exchange', { provider_id }),
};
