<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>ใบรับรองการสอบเทียบ — {{ $cal->equipment?->id_code }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: 'sarabun', 'tahoma', sans-serif; color: #1e293b; margin: 0; font-size: 11pt; }
        .header { text-align: center; padding-bottom: 8mm; border-bottom: 3px double #2563eb; margin-bottom: 8mm; }
        .header h1 { color: #2563eb; font-size: 18pt; margin: 0; letter-spacing: 1mm; }
        .header h2 { color: #475569; font-size: 12pt; margin: 2mm 0 0; font-weight: 400; }
        .header .hospital { color: #334155; font-size: 14pt; margin-top: 4mm; font-weight: 600; }
        .badge {
            display: inline-block; padding: 2mm 6mm; border-radius: 5mm;
            font-size: 14pt; font-weight: 700;
            margin: 4mm 0;
        }
        .b-pass { background: #d1fae5; color: #047857; border: 2px solid #10b981; }
        .b-fail { background: #fee2e2; color: #b91c1c; border: 2px solid #ef4444; }
        .id_code {
            font-family: monospace; font-size: 18pt; font-weight: bold;
            color: #1d4ed8; text-align: center;
            background: linear-gradient(to right, #dbeafe, #e0e7ff);
            padding: 4mm; border-radius: 3mm; margin: 4mm 0;
        }
        .equipment-name { text-align: center; font-size: 14pt; font-weight: 600; }
        .equipment-en { text-align: center; font-size: 10pt; color: #64748b; margin-bottom: 6mm; }
        table.detail { width: 100%; margin-top: 6mm; border-collapse: collapse; }
        table.detail td { padding: 3mm; vertical-align: top; }
        table.detail td.label { width: 40%; font-weight: 600; color: #475569; border-bottom: 1px dashed #cbd5e1; }
        table.detail td.value { color: #1e293b; border-bottom: 1px dashed #cbd5e1; }
        .signature {
            margin-top: 18mm;
            display: table; width: 100%;
        }
        .sign-block {
            display: table-cell; width: 50%; text-align: center;
            padding-top: 14mm; border-top: 1px solid #94a3b8;
            font-size: 10pt;
        }
        .footer {
            position: fixed; bottom: 6mm; left: 0; right: 0;
            text-align: center; font-size: 8pt; color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ใบรับรองการสอบเทียบ</h1>
        <h2>CALIBRATION CERTIFICATE</h2>
        <div class="hospital">{{ $hospital->name_th }}</div>
        <div style="font-size:9pt;color:#64748b">เลขที่ใบรับรอง: {{ $cal->certificate_no ?? 'CK-CAL-'.str_pad($cal->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="id_code">{{ $cal->equipment?->id_code }}</div>
    <div class="equipment-name">{{ $cal->equipment?->name_th }}</div>
    @if ($cal->equipment?->name_en)
        <div class="equipment-en">{{ $cal->equipment->name_en }}</div>
    @endif

    <div style="text-align:center">
        @if ($cal->result === 'PASS')
            <span class="badge b-pass">✓ ผ่านเกณฑ์การสอบเทียบ</span>
        @else
            <span class="badge b-fail">✗ ไม่ผ่านเกณฑ์</span>
        @endif
    </div>

    <table class="detail">
        <tr><td class="label">หน่วยงาน</td><td class="value">{{ $cal->equipment?->department?->name_th }}</td></tr>
        <tr><td class="label">ยี่ห้อ / รุ่น</td><td class="value">{{ $cal->equipment?->manufacturer }} {{ $cal->equipment?->model }}</td></tr>
        @if ($cal->equipment?->serial_number)
            <tr><td class="label">หมายเลข Serial</td><td class="value">{{ $cal->equipment->serial_number }}</td></tr>
        @endif
        <tr><td class="label">วันที่สอบเทียบ</td><td class="value">{{ $cal->calibrated_at?->format('d / m / Y') }}</td></tr>
        <tr><td class="label">ครบกำหนดสอบเทียบครั้งถัดไป</td><td class="value" style="color:#dc2626;font-weight:600">{{ $cal->next_due_at?->format('d / m / Y') ?? '—' }}</td></tr>
        <tr><td class="label">องค์กรสอบเทียบ</td><td class="value">{{ $cal->organization }}</td></tr>
        @if ($cal->calibrator_name)
            <tr><td class="label">ผู้สอบเทียบ</td><td class="value">{{ $cal->calibrator_name }} @if($cal->calibrator_phone)(โทร {{ $cal->calibrator_phone }})@endif</td></tr>
        @endif
        @if ($cal->controller_name)
            <tr><td class="label">เจ้าหน้าที่ผู้ควบคุม</td><td class="value">{{ $cal->controller_name }}</td></tr>
        @endif
        @if ($cal->cost)
            <tr><td class="label">ค่าใช้จ่าย</td><td class="value">{{ number_format($cal->cost, 2) }} บาท</td></tr>
        @endif
        @if ($cal->remark)
            <tr><td class="label">หมายเหตุ</td><td class="value">{{ $cal->remark }}</td></tr>
        @endif
    </table>

    <div class="signature">
        <div class="sign-block">
            ลงชื่อ ............................................<br />
            ({{ $cal->calibrator_name ?? '...........................' }})<br />
            ผู้สอบเทียบ
        </div>
        <div class="sign-block">
            ลงชื่อ ............................................<br />
            ({{ $cal->controller_name ?? '...........................' }})<br />
            ผู้ควบคุม
        </div>
    </div>

    <div class="footer">
        ออกโดย CK-MEMS เมื่อ {{ now()->format('d/m/Y H:i') }} น. · เอกสารฉบับนี้ออกโดยระบบอัตโนมัติ
    </div>
</body>
</html>
