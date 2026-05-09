<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\RepairProgressLog;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RepairWorkflowService
{
    public const TRANSITIONS = [
        RepairTicket::STATUS_PENDING => [
            RepairTicket::STATUS_ACKNOWLEDGED,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_ACKNOWLEDGED => [
            RepairTicket::STATUS_IN_PROGRESS,
            RepairTicket::STATUS_OUTSOURCED,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_IN_PROGRESS => [
            RepairTicket::STATUS_WAITING_PARTS,
            RepairTicket::STATUS_OUTSOURCED,
            RepairTicket::STATUS_REPAIRED,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_WAITING_PARTS => [
            RepairTicket::STATUS_IN_PROGRESS,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_OUTSOURCED => [
            RepairTicket::STATUS_REPAIRED,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_REPAIRED => [
            RepairTicket::STATUS_VERIFIED,
            RepairTicket::STATUS_IN_PROGRESS,
        ],
        RepairTicket::STATUS_VERIFIED => [
            RepairTicket::STATUS_CLOSED,
        ],
        RepairTicket::STATUS_CLOSED => [],
        RepairTicket::STATUS_CANCELLED => [],
    ];

    public const ACTIVE_STATUSES = [
        RepairTicket::STATUS_PENDING,
        RepairTicket::STATUS_ACKNOWLEDGED,
        RepairTicket::STATUS_IN_PROGRESS,
        RepairTicket::STATUS_WAITING_PARTS,
        RepairTicket::STATUS_OUTSOURCED,
    ];

    // SLA hours by urgency level
    private const SLA_HOURS = [
        'CRITICAL' => 1,
        'HIGH'     => 4,
        'MEDIUM'   => 24,
        'LOW'      => 168, // 7 days
    ];

    public function nextStatuses(RepairTicket $ticket): array
    {
        return self::TRANSITIONS[$ticket->status] ?? [];
    }

    // Generate next outsource ref: OSR-YYYYMM-XXXX (sequential per hospital per month)
    public function generateOutsourceRef(int $hospitalId): string
    {
        $prefix = 'OSR-' . now()->format('Ym') . '-';

        $last = RepairTicket::where('hospital_id', $hospitalId)
            ->where('outsource_ref', 'like', $prefix . '%')
            ->orderByDesc('outsource_ref')
            ->value('outsource_ref');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function transition(
        RepairTicket $ticket,
        string $toStatus,
        User $user,
        ?string $note = null,
        array $extra = []
    ): RepairTicket {
        $allowed = $this->nextStatuses($ticket);
        if (! in_array($toStatus, $allowed, true)) {
            abort(422, "ไม่สามารถเปลี่ยนสถานะจาก {$ticket->status} เป็น $toStatus ได้");
        }

        return DB::transaction(function () use ($ticket, $toStatus, $user, $note, $extra) {
            $from = $ticket->status;

            $update = ['status' => $toStatus];
            $now = now();

            if ($toStatus === RepairTicket::STATUS_ACKNOWLEDGED) {
                $update['acknowledged_at'] = $now;
                $update['assigned_to'] = $extra['assigned_to'] ?? $user->id;
            }
            if ($toStatus === RepairTicket::STATUS_IN_PROGRESS && empty($ticket->started_at)) {
                $update['started_at'] = $now;
            }
            if ($toStatus === RepairTicket::STATUS_OUTSOURCED) {
                $update['outsourced_at'] = $now;
                if (isset($extra['vendor_name'])) $update['vendor_name'] = $extra['vendor_name'];
                if (isset($extra['outsource_ref'])) $update['outsource_ref'] = $extra['outsource_ref'];
                if (isset($extra['expected_return_at'])) $update['expected_return_at'] = $extra['expected_return_at'];
            }
            if ($toStatus === RepairTicket::STATUS_REPAIRED) {
                $update['completed_at'] = $now;
                if (isset($extra['root_cause'])) $update['root_cause'] = $extra['root_cause'];
                if (isset($extra['action_taken'])) $update['action_taken'] = $extra['action_taken'];
                if (isset($extra['parts_used'])) $update['parts_used'] = $extra['parts_used'];
                if (isset($extra['repair_cost'])) $update['repair_cost'] = $extra['repair_cost'];
            }
            if ($toStatus === RepairTicket::STATUS_VERIFIED) {
                $update['verified_at'] = $now;
                $update['verified_by'] = $user->id;
            }
            if ($toStatus === RepairTicket::STATUS_CLOSED) {
                $update['closed_at'] = $now;
            }

            $ticket->update($update);

            RepairProgressLog::create([
                'repair_ticket_id' => $ticket->id,
                'from_status' => $from,
                'to_status' => $toStatus,
                'note' => $note,
                'changed_by' => $user->id,
                'changed_at' => $now,
            ]);

            // Decommission equipment when flagged as beyond repair
            if ($toStatus === RepairTicket::STATUS_CANCELLED && ! empty($extra['decommission'])) {
                $equipment = $ticket->fresh()->equipment;
                if ($equipment) {
                    $equipment->update([
                        'status'                => Equipment::STATUS_OUT_OF_SERVICE,
                        'decommissioned_at'     => $now,
                        'decommissioned_reason' => $extra['decommission_reason'] ?? null,
                    ]);
                }
            } else {
                $this->syncEquipmentStatus($ticket->fresh());
            }

            return $ticket->fresh();
        });
    }

    public function syncEquipmentStatus(RepairTicket $ticket): void
    {
        $equipment = $ticket->equipment;
        if (! $equipment) return;
        // Never override OUT_OF_SERVICE (decommissioned) status
        if ($equipment->status === Equipment::STATUS_OUT_OF_SERVICE) return;

        $hasOpenTicket = RepairTicket::where('equipment_id', $equipment->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if ($hasOpenTicket) {
            if ($equipment->status !== Equipment::STATUS_UNDER_REPAIR) {
                $equipment->update(['status' => Equipment::STATUS_UNDER_REPAIR]);
            }
        } elseif ($equipment->status === Equipment::STATUS_UNDER_REPAIR) {
            $equipment->update(['status' => Equipment::STATUS_ACTIVE]);
        }
    }

    public function calculateSlaDue(string $urgency, \Carbon\Carbon $reportedAt): \Carbon\Carbon
    {
        $hours = self::SLA_HOURS[$urgency] ?? 24;
        return $reportedAt->copy()->addHours($hours);
    }

    public function generateTicketNumber(int $hospitalId): string
    {
        $thaiYear = now()->year + 543;
        $yy = substr((string) $thaiYear, -2);
        $prefix = "RP{$yy}-";

        $lastNo = RepairTicket::where('hospital_id', $hospitalId)
            ->where('ticket_no', 'like', $prefix.'%')
            ->orderByDesc('ticket_no')
            ->lockForUpdate()
            ->value('ticket_no');

        $next = 1;
        if ($lastNo && preg_match('/-(\d+)$/', $lastNo, $m)) {
            $next = (int) $m[1] + 1;
        }

        return sprintf('%s%04d', $prefix, $next);
    }
}
