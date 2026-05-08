import client from './client';

export const calibrationsApi = {
    list: (params = {}) => client.get('/calibrations', { params }),
    show: (id) => client.get(`/calibrations/${id}`),
    store: (data) => client.post('/calibrations', data),
    summary: () => client.get('/calibrations/summary'),
};
