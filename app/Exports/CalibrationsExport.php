<?php

namespace App\Exports;

use App\Models\Calibration;
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

class CalibrationsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $hospitalId,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
    ) {
    }

    public function query()
    {
        $q = Calibration::with('equipment.department:id,code')
            ->where('hospital_id', $this->hospitalId);

        if ($this->from) $q->where('calibrated_at', '>=', $this->from);
        if ($this->to)   $q->where('calibrated_at', '<=', $this->to);

        return $q->orderByDesc('calibrated_at');
    }

    public function headings(): array
    {
        return [
            'ลำดับ', 'วันที่สอบ', 'รหัสเครื่อง', 'ชื่อเครื่องมือ', 'หน่วยงาน',
            'องค์กรสอบเทียบ', 'ผู้สอบเทียบ', 'เบอร์โทร', 'ผู้ควบคุม',
            'ผลสอบ', 'เลขที่ใบรับรอง', 'ค่าใช้จ่าย', 'ครบกำหนดถัดไป',
        ];
    }

    public function map($c): array
    {
        static $row = 0;
        $row++;
        return [
            $row,
            $c->calibrated_at?->format('Y-m-d'),
            $c->equipment?->id_code,
            $c->equipment?->name_th,
            $c->equipment?->department?->code,
            $c->organization,
            $c->calibrator_name,
            $c->calibrator_phone,
            $c->controller_name,
            $c->result === 'PASS' ? 'ผ่าน' : 'ไม่ผ่าน',
            $c->certificate_no,
            $c->cost,
            $c->next_due_at?->format('Y-m-d'),
        ];
    }

    public function title(): string
    {
        return 'การสอบเทียบ';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}
