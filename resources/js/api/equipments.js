import client from './client';

export const equipmentsApi = {
    list: (params = {}) => client.get('/equipments', { params }),
    show: (id) => client.get(`/equipments/${id}`),
    store: (data) => client.post('/equipments', data),
    update: (id, data) => client.put(`/equipments/${id}`, data),
    destroy: (id) => client.delete(`/equipments/${id}`),
    previewIdCode: (department_id, equipment_code_id) =>
        client.get('/equipments/preview-id-code', { params: { department_id, equipment_code_id } }),
};
