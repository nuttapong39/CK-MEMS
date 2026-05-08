import client from './client';

export const mophApi = {
    settings: () => client.get('/moph/settings'),
    updateSettings: (data) => client.put('/moph/settings', data),
    test: () => client.post('/moph/test'),
    templates: () => client.get('/moph/templates'),
    showTemplate: (id) => client.get(`/moph/templates/${id}`),
    updateTemplate: (id, data) => client.put(`/moph/templates/${id}`, data),
    previewTemplate: (data) => client.post('/moph/templates/preview', data),
    logs: (params = {}) => client.get('/moph/logs', { params }),
};
