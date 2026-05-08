<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>QR Codes — {{ $hospital->name_th }}</title>
    <style>
        @page { margin: 8mm; }
        * { box-sizing: border-box; }
        body { font-family: 'sarabun', 'tahoma', sans-serif; margin: 0; padding: 0; font-size: 10pt; }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4mm;
        }
        .label {
            width: calc({{ $qr_size_mm }}mm + 30mm);
            border: 1px dashed #94a3b8;
            border-radius: 3mm;
            padding: 3mm;
            page-break-inside: avoid;
            margin-bottom: 4mm;
        }
        .qr {
            width: {{ $qr_size_mm }}mm;
            height: {{ $qr_size_mm }}mm;
            display: block;
            margin: 0 auto 2mm;
        }
        .id_code {
            font-family: monospace;
            font-weight: bold;
            text-align: center;
            color: #1d4ed8;
            font-size: 11pt;
            margin-bottom: 1.5mm;
        }
        .field {
            font-size: 8.5pt;
            line-height: 1.35;
            color: #334155;
            margin-bottom: 0.5mm;
            text-align: center;
        }
        .field-label {
            color: #64748b;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        h1 { font-size: 12pt; margin: 0 0 4mm; color: #2563eb; }
    </style>
</head>
<body>
    <h1>QR-Code เครื่องมือแพทย์ — {{ $hospital->name_th }}</h1>
    <div class="grid">
        @foreach ($rows as $row)
            <div class="label">
                <img class="qr" src="{{ $row['qr_data_uri'] }}" alt="QR" />
                <div class="id_code">{{ $row['equipment']->id_code }}</div>
                @foreach ($row['fields'] as $key => $value)
                    @if ($key !== 'id_code' && $value)
                        <div class="field">
                            <span class="field-label">{{ ['name_th'=>'ชื่อไทย','name_en'=>'อังกฤษ','manufacturer'=>'ยี่ห้อ','model'=>'รุ่น','serial_number'=>'SN','department'=>'หน่วยงาน'][$key] ?? $key }}:</span>
                            {{ $value }}
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</body>
</html>
