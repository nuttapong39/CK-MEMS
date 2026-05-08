<?php

namespace App\Http\Requests;

use App\Models\RepairTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'staff']);
    }

    public function rules(): array
    {
        $statuses = [
            RepairTicket::STATUS_ACKNOWLEDGED,
            RepairTicket::STATUS_IN_PROGRESS,
            RepairTicket::STATUS_WAITING_PARTS,
            RepairTicket::STATUS_REPAIRED,
            RepairTicket::STATUS_CLOSED,
            RepairTicket::STATUS_CANCELLED,
        ];

        return [
            'to_status' => ['required', Rule::in($statuses)],
            'note' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'root_cause' => ['nullable', 'string'],
            'action_taken' => ['nullable', 'string'],
            'parts_used' => ['nullable', 'string'],
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
