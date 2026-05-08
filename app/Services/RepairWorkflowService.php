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
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_IN_PROGRESS => [
            RepairTicket::STATUS_WAITING_PARTS,
            RepairTicket::STATUS_REPAIRED,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_WAITING_PARTS => [
            RepairTicket::STATUS_IN_PROGRESS,
            RepairTicket::STATUS_CANCELLED,
        ],
        RepairTicket::STATUS_REPAIRED => [
            RepairTicket::STATUS_CLOSED,
            RepairTicket::STATUS_IN_PROGRESS,
        ],
        RepairTicket::STATUS_CLOSED => [],
        RepairTicket::STATUS_CANCELLED => [],
    ];

    public const ACTIVE_STATUSES = [
        RepairTicket::STATUS_PENDING,
        RepairTicket::STATUS_ACKNOWLEDGED,
        RepairTicket::STATUS_IN_PROGRESS,
        RepairTicket::STATUS_WAITING_PARTS,
    ];

    public function nextStatuses(RepairTicket $ticket): array
    {
        return self::TRANSITIONS[$ticket->status] ?? [];
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
            if ($toStatus === RepairTicket::STATUS_REPAIRED) {
                $update['completed_at'] = $now;
                if (isset($extra['root_cause'])) $update['root_cause'] = $extra['root_cause'];
                if (isset($extra['action_taken'])) $update['action_taken'] = $extra['action_taken'];
                if (isset($extra['parts_used'])) $update['parts_used'] = $extra['parts_used'];
                if (isset($extra['repair_cost'])) $update['repair_cost'] = $extra['repair_cost'];
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

            $this->syncEquipmentStatus($ticket->fresh());

            return $ticket->fresh();
        });
    }

    public function syncEquipmentStatus(RepairTicket $ticket): void
    {
        $equipment = $ticket->equipment;
        if (! $equipment) return;

        $hasOpenTicket = RepairTicket::where('equipment_id', $equipment->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if ($hasOpenTicket) {
            if ($equipment->status !== Equipment::class && $equipment->status !== 'UNDER_REPAIR') {
                $equipment->update(['status' => 'UNDER_REPAIR']);
            }
        } elseif ($equipment->status === 'UNDER_REPAIR') {
            $equipment->update(['status' => 'ACTIVE']);
        }
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
