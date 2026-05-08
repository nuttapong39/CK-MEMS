<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>รายการเครื่องมือแพทย์ — {{ $hospital->name_th }}</title>
    <style>
        @page { margin: 12mm 8mm; }
        body { font-family: 'sarabun', 'tahoma', sans-serif; font-size: 9pt; color: #1e293b; margin: 0; }
        h1 { color: #2563eb; font-size: 14pt; margin: 0 0 2mm; }
        .meta { font-size: 9pt; color: #64748b; margin-bottom: 4mm; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2563eb; color: white; padding: 4px 6px; text-align: left; font-size: 9pt; }
        td { padding: 3px 6px; border-bottom: 1px solid #e2e8f0; font-size: 8.5pt; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .id_code { font-family: monospace; color: #1d4ed8; font-weight: bold; }
        .badge { padding: 1px 6px; border-radius: 8px; font-size: 7.5pt; font-weight: bold; }
        .b-active { background: #d1fae5; color: #047857; }
        .b-broken { background: #fee2e2; color: #b91c1c; }
        .b-other { background: #f1f5f9; color: #64748b; }
        .footer { font-size: 7.5pt; color: #94a3b8; text-align: center; margin-top: 6mm; }
    </style>
</head>
<body>
    <h1>รายการเครื่องมือแพทย์ — {{ $hospital->name_th }}</h1>
    <div class="meta">
        ทั้งหมด {{ $items->count() }} รายการ ·
        ออกรายงานเมื่อ {{ $generated_at->format('d/m/Y H:i น.') }}
        @if (! empty($filters))
            · ตัวกรอง: {{ collect($filters)->filter()->map(fn($v,$k) => "$k=$v")->implode(', ') }}
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:5%">ลำดับ</th>
                <th style="width:10%">ID Code</th>
                <th style="width:25%">ชื่อเครื่องมือ</th>
                <th style="width:8%">หน่วยงาน</th>
                <th style="width:12%">ยี่ห้อ/รุ่น</th>
                <th style="width:12%">SN</th>
                <th style="width:8%">ปีงบ</th>
                <th style="width:10%">สอบเทียบ</th>
                <th style="width:10%">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statusMap = [
                    'ACTIVE' => ['b-active', 'ใช้งาน'],
                    'BROKEN' => ['b-broken', 'ชำรุด'],
                    'UNDER_REPAIR' => ['b-other', 'กำลังซ่อม'],
                    'RETIRED' => ['b-other', 'จำหน่าย'],
                    'PENDING_DISPOSAL' => ['b-other', 'รอจำหน่าย'],
                ];
                $calMap = ['DSS' => 'ศูนย์ วศ.', 'PRIVATE' => 'เอกชน', 'BOTH' => 'ทั้งคู่', 'NONE' => '—'];
            @endphp
            @foreach ($items as $i => $e)
                @php [$cls, $statusLabel] = $statusMap[$e->status] ?? ['b-other', $e->status]; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="id_code">{{ $e->id_code }}</td>
                    <td>
                        <div>{{ $e->name_th }}</div>
                        <div style="color:#94a3b8;font-size:7.5pt">{{ $e->name_en }}</div>
                    </td>
                    <td>{{ $e->department?->code }}</td>
                    <td>{{ $e->manufacturer }} {{ $e->model }}</td>
                    <td>{{ $e->serial_number }}</td>
                    <td>{{ $e->fiscal_year }}</td>
                    <td>{{ $calMap[$e->calibration_by] ?? $e->calibration_by }}</td>
                    <td><span class="badge {{ $cls }}">{{ $statusLabel }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        CK-MEMS · Medical Equipment Management System · ออกโดยระบบ
    </div>
</body>
</html>
