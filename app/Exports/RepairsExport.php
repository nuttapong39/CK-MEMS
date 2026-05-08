<?php

namespace App\Exports;

use App\Models\RepairTicket;
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

class RepairsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $hospitalId,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
        private readonly ?string $status = null,
    ) {
    }

    public function query()
    {
        $q = RepairTicket::with('equipment.department:id,code', 'reporter:id,name,full_name')
            ->where('hospital_id', $this->hospitalId);

        if ($this->from) $q->where('reported_at', '>=', $this->from);
        if ($this->to)   $q->where('reported_at', '<=', $this->to.' 23:59:59');
        if ($this->status) $q->where('status', $this->status);

        return $q->orderByDesc('reported_at');
    }

    public function headings(): array
    {
        return [
            'ลำดับ', 'หมายเลขใบงาน', 'วันที่แจ้ง', 'รหัสเครื่อง', 'ชื่อเครื่องมือ',
            'หน่วยงาน', 'ผู้แจ้ง', 'อาการ', 'ระดับเร่งด่วน', 'สถานะ',
            'สาเหตุ', 'การแก้ไข', 'ค่าซ่อม',
        ];
    }

    public function map($t): array
    {
        static $row = 0;
        $row++;
        $statusMap = [
            'PENDING' => 'รอรับเรื่อง', 'ACKNOWLEDGED' => 'รับเรื่องแล้ว',
            'IN_PROGRESS' => 'กำลังซ่อม', 'WAITING_PARTS' => 'รออะไหล่',
            'REPAIRED' => 'ซ่อมเสร็จ', 'CLOSED' => 'ปิดงาน', 'CANCELLED' => 'ยกเลิก',
        ];
        $urgencyMap = ['CRITICAL' => 'วิกฤติ', 'HIGH' => 'สูง', 'MEDIUM' => 'ปานกลาง', 'LOW' => 'ต่ำ'];

        return [
            $row,
            $t->ticket_no,
            $t->reported_at?->format('Y-m-d H:i'),
            $t->equipment?->id_code,
            $t->equipment?->name_th,
            $t->equipment?->department?->code,
            $t->reporter?->full_name ?? $t->reporter?->name,
            $t->symptom,
            $urgencyMap[$t->urgency] ?? $t->urgency,
            $statusMap[$t->status] ?? $t->status,
            $t->root_cause,
            $t->action_taken,
            $t->repair_cost,
        ];
    }

    public function title(): string
    {
        return 'ประวัติการซ่อม';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EF4444']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}
