<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\QrcodeTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QrCodeController extends Controller
{
    public function png(Request $request, Equipment $equipment): HttpResponse
    {
        abort_if($equipment->hospital_id !== $request->user()->hospital_id, 404);

        $size = max(80, min((int) $request->integer('size', 300), 800));
        $url = $this->equipmentUrl($equipment->id_code);

        $result = (new Builder(
            writer: new PngWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    public function templates(Request $request): JsonResponse
    {
        $list = QrcodeTemplate::where('hospital_id', $request->user()->hospital_id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return response()->json($list);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'paper_size' => ['required', 'string', 'max:32'],
            'qr_size_mm' => ['required', 'integer', 'min:10', 'max:200'],
            'fields_to_show' => ['required', 'array'],
            'fields_to_show.*' => ['string'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            QrcodeTemplate::where('hospital_id', $request->user()->hospital_id)
                ->update(['is_default' => false]);
        }

        $template = QrcodeTemplate::create([
            ...$data,
            'hospital_id' => $request->user()->hospital_id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($template, 201);
    }

    public function destroyTemplate(QrcodeTemplate $template, Request $request): JsonResponse
    {
        abort_if($template->hospital_id !== $request->user()->hospital_id, 404);
        $template->delete();

        return response()->json(['message' => 'ลบเรียบร้อย']);
    }

    public function batchPdf(Request $request)
    {
        $data = $request->validate([
            'equipment_ids' => ['required', 'array', 'min:1', 'max:200'],
            'equipment_ids.*' => ['integer', 'exists:equipments,id'],
            'paper_size' => ['required', 'string'],
            'qr_size_mm' => ['required', 'integer', 'min:10', 'max:200'],
            'fields_to_show' => ['required', 'array'],
            'fields_to_show.*' => ['string'],
        ]);

        $hospitalId = $request->user()->hospital_id;
        $equipments = Equipment::with('department:id,code,name_th', 'equipmentCode:id,code')
            ->where('hospital_id', $hospitalId)
            ->whereIn('id', $data['equipment_ids'])
            ->orderBy('id_code')
            ->get();

        $writer = new PngWriter();
        $rows = $equipments->map(function ($eq) use ($writer, $data) {
            $url = $this->equipmentUrl($eq->id_code);
            $result = (new Builder(
                writer: $writer,
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 420,
                margin: 8,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            ))->build();

            return [
                'equipment' => $eq,
                'qr_data_uri' => 'data:image/png;base64,'.base64_encode($result->getString()),
                'fields' => $this->extractFields($eq, $data['fields_to_show']),
            ];
        });

        $pdf = Pdf::loadView('pdf.qrcode-batch', [
            'rows' => $rows,
            'paper_size' => $data['paper_size'],
            'qr_size_mm' => $data['qr_size_mm'],
            'hospital' => $request->user()->hospital,
        ]);

        $pdf->setPaper(strtolower($data['paper_size']), 'portrait');

        return $pdf->download(sprintf('qrcodes_%s.pdf', now()->format('Ymd_His')));
    }

    private function equipmentUrl(string $idCode): string
    {
        return url('/qr/'.$idCode);
    }

    private function extractFields(Equipment $eq, array $fields): array
    {
        $map = [
            'id_code' => $eq->id_code,
            'name_th' => $eq->name_th,
            'name_en' => $eq->name_en,
            'manufacturer' => $eq->manufacturer,
            'model' => $eq->model,
            'serial_number' => $eq->serial_number,
            'department' => $eq->department?->name_th,
        ];

        return collect($fields)->mapWithKeys(fn ($k) => [$k => $map[$k] ?? ''])->toArray();
    }
}
