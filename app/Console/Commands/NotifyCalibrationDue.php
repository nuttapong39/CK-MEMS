<?php

namespace App\Console\Commands;

use App\Models\Calibration;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class NotifyCalibrationDue extends Command
{
    protected $signature = 'ckmems:notify-calibration-due
                            {--days=30 : Look-ahead window in days}
                            {--dry-run : Print only, do not send}';

    protected $description = 'Find calibrations whose next_due_at falls within the look-ahead window and trigger MOPH alerts';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $items = Calibration::with('equipment.department', 'equipment.hospital.mophAlertSetting')
            ->whereBetween('next_due_at', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('next_due_at')
            ->get();

        if ($items->isEmpty()) {
            $this->info("No calibrations due within $days days.");
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d calibration(s) due within %d days', $items->count(), $days));

        foreach ($items as $cal) {
            $line = sprintf(
                ' - [%s] %s | %s | next_due_at=%s | hospital=%s',
                $cal->equipment?->id_code ?? '?',
                $cal->equipment?->name_th ?? '?',
                $cal->equipment?->department?->name_th ?? '?',
                $cal->next_due_at?->toDateString(),
                $cal->equipment?->hospital?->code ?? '?',
            );
            $this->line($line);

            if ($dryRun) continue;

            // Hook for Phase 7 (MOPH Alert dispatch)
            $hospital = $cal->equipment?->hospital;
            $setting = $hospital?->mophAlertSetting;
            if (! $setting?->is_enabled || ! $setting->notify_on_calibration) {
                continue;
            }

            NotificationLog::create([
                'hospital_id' => $hospital->id,
                'template_key' => 'CALIBRATION_DUE',
                'event_signature' => 'calibration.due:'.$cal->id,
                'payload_snapshot' => [
                    'equipment_id_code' => $cal->equipment->id_code,
                    'equipment_name_th' => $cal->equipment->name_th,
                    'next_due_at' => $cal->next_due_at?->toDateString(),
                ],
                'response_code' => null,
                'response_body' => 'queued (MOPH dispatch wired in Phase 7)',
                'sent_at' => now(),
            ]);
        }

        return self::SUCCESS;
    }
}
