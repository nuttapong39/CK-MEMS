<?php

namespace App\Exports;

use App\Models\Equipment;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EquipmentsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $hospitalId,
        private readonly array $filters = []
    ) {
    }

    public function query()
    {
        $q = Equipment::with('department:id,code,name_th', 'equipmentCode:id,code')
            ->where('hospital_id', $this->hospitalId);

        if (! empty($this->filters['department_id'])) {
            $q->where('department_id', $this->filters['department_id']);
        }
        if (! empty($this->filters['status'])) {
            $q->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['fiscal_year'])) {
            $q->where('fiscal_year', $this->filters['fiscal_year']);
        }

        return $q->orderBy('id_code');
    }

    public function headings(): array
    {
        return [
            'ลำดับ', 'รหัสเครื่อง (ID Code)', 'หน่วยงาน', 'รหัสครุภัณฑ์',
            'ชื่อ (ไทย)', 'ชื่อ (อังกฤษ)', 'ยี่ห้อ', 'รุ่น', 'Serial',
            'ปีงบประมาณ', 'สอบเทียบโดย', 'สถานะ', 'หมายเหตุ',
        ];
    }

    public function map($e): array
    {
        static $row = 0;
        $row++;
        return [
            $row,
            $e->id_code,
            $e->department?->code.' — '.$e->department?->name_th,
            $e->asset_number,
            $e->name_th,
            $e->name_en,
            $e->manufacturer,
            $e->model,
            $e->serial_number,
            $e->fiscal_year,
            ['DSS' => 'ศูนย์ วศ.', 'PRIVATE' => 'เอกชน', 'BOTH' => 'ทั้งสองหน่วย', 'NONE' => '—'][$e->calibration_by] ?? $e->calibration_by,
            ['ACTIVE' => 'ใช้งาน', 'BROKEN' => 'ชำรุด', 'UNDER_REPAIR' => 'กำลังซ่อม', 'RETIRED' => 'จำหน่าย', 'PENDING_DISPOSAL' => 'รอแทงจำหน่าย'][$e->status] ?? $e->status,
            $e->note,
        ];
    }

    public function title(): string
    {
        return 'เครื่องมือแพทย์';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}
