import client from './client';

export const repairsApi = {
    list: (params = {}) => client.get('/repairs', { params }),
    show: (id) => client.get(`/repairs/${id}`),
    store: (data) => client.post('/repairs', data),
    transition: (id, data) => client.post(`/repairs/${id}/transition`, data),
    summary: () => client.get('/repairs/summary'),
};
