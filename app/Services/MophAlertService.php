<?php

namespace App\Services;

use App\Models\FlexMessageTemplate;
use App\Models\Hospital;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MophAlertService
{
    public function __construct(private readonly FlexMessageBuilder $flexBuilder)
    {
    }

    /**
     * Try to dispatch a MOPH alert. Failures are logged and never throw.
     */
    public function notify(
        Hospital $hospital,
        string $templateKey,
        array $variables = [],
        ?string $eventSignature = null,
        ?string $textOverride = null
    ): bool {
        $setting = $hospital->mophAlertSetting;

        if (! $setting || ! $setting->is_enabled) {
            return false;
        }

        // Per-event toggles
        $toggleMap = [
            FlexMessageTemplate::KEY_CREATE_EQUIPMENT => 'notify_on_create_equipment',
            FlexMessageTemplate::KEY_REPAIR_REQUEST => 'notify_on_repair_request',
            FlexMessageTemplate::KEY_REPAIR_ACKNOWLEDGED => 'notify_on_repair_progress',
            FlexMessageTemplate::KEY_REPAIR_IN_PROGRESS => 'notify_on_repair_progress',
            FlexMessageTemplate::KEY_REPAIR_COMPLETED => 'notify_on_repair_progress',
            FlexMessageTemplate::KEY_CALIBRATION_DONE => 'notify_on_calibration',
            FlexMessageTemplate::KEY_CALIBRATION_DUE => 'notify_on_calibration',
        ];
        $toggleField = $toggleMap[$templateKey] ?? null;
        if ($toggleField && ! $setting->{$toggleField}) {
            return false;
        }

        $template = FlexMessageTemplate::where('hospital_id', $hospital->id)
            ->where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            $this->logFailure($hospital->id, $templateKey, $eventSignature, ['reason' => 'template_not_found']);
            return false;
        }

        try {
            $rendered = $this->flexBuilder->render($template->json_payload, $variables);
            $textMsg = $textOverride ?? $template->name;
            $payload = $this->flexBuilder->buildPayload($rendered, $textMsg, $template->alt_text);
        } catch (\Throwable $e) {
            $this->logFailure($hospital->id, $templateKey, $eventSignature, ['reason' => 'render_error', 'message' => $e->getMessage()]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                    'client-key' => $setting->client_key,
                    'secret-key' => $setting->secret_key,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post($setting->endpoint_url.'?messages=yes', $payload);

            NotificationLog::create([
                'hospital_id' => $hospital->id,
                'template_key' => $templateKey,
                'event_signature' => $eventSignature,
                'payload_snapshot' => $payload,
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 4000),
                'sent_at' => now(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            $this->logFailure($hospital->id, $templateKey, $eventSignature, ['reason' => 'http_error', 'message' => $e->getMessage()]);
            Log::warning('MophAlert HTTP error: '.$e->getMessage(), ['template' => $templateKey]);
            return false;
        }
    }

    /**
     * Send a synthetic test alert using current settings (does not depend on per-event toggles).
     */
    public function sendTest(Hospital $hospital): array
    {
        $setting = $hospital->mophAlertSetting;
        if (! $setting) {
            return ['ok' => false, 'message' => 'ยังไม่ได้ตั้งค่า MOPH Alert'];
        }

        $payload = [
            'messages' => [
                ['type' => 'text', 'text' => '[ทดสอบระบบ CK-MEMS] '.$hospital->name_th],
            ],
        ];

        try {
            $response = Http::withHeaders([
                    'client-key' => $setting->client_key,
                    'secret-key' => $setting->secret_key,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post($setting->endpoint_url.'?messages=yes', $payload);

            $setting->update([
                'last_test_at' => now(),
                'last_test_status' => $response->successful() ? ('OK ('.$response->status().')') : ('FAILED ('.$response->status().')'),
            ]);

            NotificationLog::create([
                'hospital_id' => $hospital->id,
                'template_key' => 'TEST',
                'event_signature' => 'manual_test',
                'payload_snapshot' => $payload,
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 4000),
                'sent_at' => now(),
            ]);

            return [
                'ok' => $response->successful(),
                'status_code' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ];
        } catch (\Throwable $e) {
            $setting->update(['last_test_at' => now(), 'last_test_status' => 'ERROR: '.$e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function logFailure(int $hospitalId, string $templateKey, ?string $eventSignature, array $reason): void
    {
        NotificationLog::create([
            'hospital_id' => $hospitalId,
            'template_key' => $templateKey,
            'event_signature' => $eventSignature,
            'payload_snapshot' => $reason,
            'response_code' => null,
            'response_body' => json_encode($reason, JSON_UNESCAPED_UNICODE),
            'sent_at' => now(),
        ]);
    }

    public function buildEquipmentVariables($equipment): array
    {
        $equipment->loadMissing('department');
        return [
            'equipment' => [
                'id' => $equipment->id,
                'id_code' => $equipment->id_code,
                'name_th' => $equipment->name_th,
                'name_en' => $equipment->name_en,
                'manufacturer' => $equipment->manufacturer,
                'model' => $equipment->model,
                'serial_number' => $equipment->serial_number,
                'department' => $equipment->department?->name_th,
                'fiscal_year' => $equipment->fiscal_year,
            ],
        ];
    }

    public function buildRepairVariables($ticket): array
    {
        $ticket->loadMissing(['equipment.department', 'reporter']);
        return [
            ...$this->buildEquipmentVariables($ticket->equipment),
            'ticket' => [
                'ticket_no' => $ticket->ticket_no,
                'symptom' => $ticket->symptom,
                'urgency' => $ticket->urgency,
                'status' => $ticket->status,
                'reported_at' => $ticket->reported_at?->format('d/m/Y H:i'),
                'reporter_name' => $ticket->reporter?->full_name ?? $ticket->reporter?->name,
            ],
        ];
    }

    public function buildCalibrationVariables($calibration): array
    {
        $calibration->loadMissing('equipment.department');
        return [
            ...$this->buildEquipmentVariables($calibration->equipment),
            'calibration' => [
                'organization' => $calibration->organization,
                'calibrated_at' => $calibration->calibrated_at?->format('d/m/Y'),
                'next_due_at' => $calibration->next_due_at?->format('d/m/Y'),
                'result' => $calibration->result === 'PASS' ? 'ผ่าน' : 'ไม่ผ่าน',
            ],
        ];
    }
}
