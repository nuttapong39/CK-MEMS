import client from './client';

export const qrcodeApi = {
    pngUrl: (equipmentId, size = 300) => `/api/v1/qrcode/${equipmentId}/png?size=${size}`,
    templates: () => client.get('/qrcode/templates'),
    storeTemplate: (data) => client.post('/qrcode/templates', data),
    destroyTemplate: (id) => client.delete(`/qrcode/templates/${id}`),
    batchPdf: (data) => client.post('/qrcode/batch-pdf', data, { responseType: 'blob' }),
};
