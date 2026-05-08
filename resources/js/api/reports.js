// Build report download URLs (server returns binary, we link directly)
import client from './client';

const baseDownload = async (path, params = {}) => {
    const { data } = await client.get(path, { params, responseType: 'blob' });
    return data;
};

export const reportsApi = {
    equipmentsExcel: (params) => baseDownload('/reports/equipments/excel', params),
    equipmentsPdf:   (params) => baseDownload('/reports/equipments/pdf',   params),
    repairsExcel:    (params) => baseDownload('/reports/repairs/excel',    params),
    repairsPdf:      (params) => baseDownload('/reports/repairs/pdf',      params),
    calibrationsExcel: (params) => baseDownload('/reports/calibrations/excel', params),
    calibrationCertificate: (calibrationId) => baseDownload(`/reports/calibrations/${calibrationId}/certificate`),
};

export function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
}
