<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'staff', 'user']);
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'integer', 'exists:equipments,id'],
            'reported_at' => ['required', 'date'],
            'symptom' => ['required', 'string', 'min:3'],
            'urgency' => ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }
}
