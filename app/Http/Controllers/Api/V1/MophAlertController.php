<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FlexMessageTemplate;
use App\Models\MophAlertSetting;
use App\Models\NotificationLog;
use App\Services\FlexMessageBuilder;
use App\Services\MophAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MophAlertController extends Controller
{
    public function __construct(
        private readonly MophAlertService $service,
        private readonly FlexMessageBuilder $builder,
    ) {
    }

    public function settings(Request $request): JsonResponse
    {
        $hospital = $request->user()->hospital;
        $setting = MophAlertSetting::firstOrCreate(['hospital_id' => $hospital->id]);

        return response()->json([
            'is_enabled' => $setting->is_enabled,
            'endpoint_url' => $setting->endpoint_url,
            'client_key' => $setting->client_key,
            'has_secret_key' => ! empty($setting->secret_key),
            'notify_on_create_equipment' => $setting->notify_on_create_equipment,
            'notify_on_repair_request' => $setting->notify_on_repair_request,
            'notify_on_repair_progress' => $setting->notify_on_repair_progress,
            'notify_on_calibration' => $setting->notify_on_calibration,
            'last_test_at' => $setting->last_test_at,
            'last_test_status' => $setting->last_test_status,
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_enabled' => ['boolean'],
            'endpoint_url' => ['nullable', 'url', 'max:500'],
            'client_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'notify_on_create_equipment' => ['boolean'],
            'notify_on_repair_request' => ['boolean'],
            'notify_on_repair_progress' => ['boolean'],
            'notify_on_calibration' => ['boolean'],
        ]);

        $hospital = $request->user()->hospital;
        $setting = MophAlertSetting::firstOrCreate(['hospital_id' => $hospital->id]);

        // Don't overwrite secret if blank
        if (array_key_exists('secret_key', $data) && $data['secret_key'] === null) {
            unset($data['secret_key']);
        }

        $setting->update($data);

        return $this->settings($request);
    }

    public function test(Request $request): JsonResponse
    {
        $hospital = $request->user()->hospital;
        $result = $this->service->sendTest($hospital);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function templates(Request $request): JsonResponse
    {
        $templates = FlexMessageTemplate::where('hospital_id', $request->user()->hospital_id)
            ->orderBy('key')
            ->get(['id', 'key', 'name', 'alt_text', 'json_payload', 'is_active', 'updated_at']);

        return response()->json($templates);
    }

    public function showTemplate(FlexMessageTemplate $template, Request $request): JsonResponse
    {
        abort_if($template->hospital_id !== $request->user()->hospital_id, 404);

        return response()->json($template);
    }

    public function updateTemplate(Request $request, FlexMessageTemplate $template): JsonResponse
    {
        abort_if($template->hospital_id !== $request->user()->hospital_id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'alt_text' => ['sometimes', 'string', 'max:255'],
            'json_payload' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Validate JSON payload if provided
        if (isset($data['json_payload'])) {
            try {
                json_decode($data['json_payload'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return response()->json(['errors' => ['json_payload' => ['JSON ไม่ถูกต้อง: '.$e->getMessage()]]], 422);
            }
        }

        $template->update([...$data, 'updated_by' => $request->user()->id]);

        return response()->json($template->fresh());
    }

    public function previewTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'json_payload' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
        ]);

        try {
            $rendered = $this->builder->render($data['json_payload'], $data['variables'] ?? $this->sampleVariables());

            return response()->json([
                'rendered' => $rendered,
            ]);
        } catch (\JsonException $e) {
            return response()->json(['errors' => ['json_payload' => ['JSON ไม่ถูกต้อง: '.$e->getMessage()]]], 422);
        }
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = NotificationLog::where('hospital_id', $request->user()->hospital_id)
            ->orderByDesc('sent_at')
            ->limit(min($request->integer('limit', 50), 200))
            ->get(['id', 'template_key', 'event_signature', 'response_code', 'response_body', 'sent_at']);

        return response()->json($logs);
    }

    private function sampleVariables(): array
    {
        return [
            'equipment' => [
                'id_code' => 'CM-WA-DEF-01',
                'name_th' => 'เครื่องกระตุกหัวใจ ตัวอย่าง',
                'name_en' => 'Defibrillator',
                'manufacturer' => 'NIHON KOHDEN',
                'model' => 'TEC-5521K',
                'department' => 'งานผู้ป่วยใน',
            ],
            'ticket' => [
                'ticket_no' => 'RP69-0001',
                'symptom' => 'หน้าจอแสดงผลกระตุก',
                'urgency' => 'HIGH',
                'reported_at' => now()->format('d/m/Y H:i'),
                'reporter_name' => 'คุณทดสอบ',
            ],
            'calibration' => [
                'organization' => 'ศูนย์ วศ.',
                'calibrated_at' => now()->format('d/m/Y'),
                'next_due_at' => now()->addYear()->format('d/m/Y'),
                'result' => 'ผ่าน',
            ],
        ];
    }
}
