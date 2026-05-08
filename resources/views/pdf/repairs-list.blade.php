<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>ประวัติการซ่อม — {{ $hospital->name_th }}</title>
    <style>
        @page { margin: 12mm 8mm; }
        body { font-family: 'sarabun', 'tahoma', sans-serif; font-size: 9pt; color: #1e293b; margin: 0; }
        h1 { color: #ef4444; font-size: 14pt; margin: 0 0 2mm; }
        .meta { font-size: 9pt; color: #64748b; margin-bottom: 4mm; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #ef4444; color: white; padding: 4px 6px; text-align: left; font-size: 9pt; }
        td { padding: 3px 6px; border-bottom: 1px solid #e2e8f0; font-size: 8.5pt; vertical-align: top; }
        tr:nth-child(even) td { background: #fef2f2; }
        .id_code { font-family: monospace; color: #1d4ed8; font-weight: bold; }
        .ticket_no { font-family: monospace; font-weight: bold; color: #dc2626; }
        .footer { font-size: 7.5pt; color: #94a3b8; text-align: center; margin-top: 6mm; }
    </style>
</head>
<body>
    <h1>รายงานประวัติการแจ้งซ่อม — {{ $hospital->name_th }}</h1>
    <div class="meta">
        ทั้งหมด {{ $items->count() }} ใบงาน
        @if ($from) · ตั้งแต่ {{ $from }} @endif
        @if ($to) · ถึง {{ $to }} @endif
        · ออกรายงานเมื่อ {{ $generated_at->format('d/m/Y H:i น.') }}
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:5%">ลำดับ</th>
                <th style="width:9%">หมายเลข</th>
                <th style="width:11%">วันที่แจ้ง</th>
                <th style="width:9%">ID Code</th>
                <th style="width:18%">เครื่องมือ</th>
                <th style="width:6%">หน่วยงาน</th>
                <th style="width:11%">ผู้แจ้ง</th>
                <th style="width:18%">อาการ</th>
                <th style="width:7%">เร่งด่วน</th>
                <th style="width:6%">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statusMap = [
                    'PENDING' => 'รอรับ', 'ACKNOWLEDGED' => 'รับแล้ว',
                    'IN_PROGRESS' => 'ซ่อม', 'WAITING_PARTS' => 'รออะไหล่',
                    'REPAIRED' => 'เสร็จ', 'CLOSED' => 'ปิด', 'CANCELLED' => 'ยกเลิก',
                ];
                $urgencyMap = ['CRITICAL' => 'วิกฤติ', 'HIGH' => 'สูง', 'MEDIUM' => 'กลาง', 'LOW' => 'ต่ำ'];
            @endphp
            @foreach ($items as $i => $t)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="ticket_no">{{ $t->ticket_no }}</td>
                    <td>{{ $t->reported_at?->format('d/m/Y H:i') }}</td>
                    <td class="id_code">{{ $t->equipment?->id_code }}</td>
                    <td>{{ $t->equipment?->name_th }}</td>
                    <td>{{ $t->equipment?->department?->code }}</td>
                    <td>{{ $t->reporter?->full_name ?? $t->reporter?->name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($t->symptom, 80) }}</td>
                    <td>{{ $urgencyMap[$t->urgency] ?? $t->urgency }}</td>
                    <td>{{ $statusMap[$t->status] ?? $t->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">CK-MEMS · ออกโดยระบบ</div>
</body>
</html>
