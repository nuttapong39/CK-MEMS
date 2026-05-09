<?php

namespace App\Http\Requests;

use App\Models\Equipment;
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
            'equipment_id' => [
                'required', 'integer',
                Rule::exists('equipments', 'id')->where(function ($q) {
                    $q->whereNot('status', Equipment::STATUS_OUT_OF_SERVICE);
                }),
            ],
            'reported_at' => ['required', 'date'],
            'symptom'     => ['required', 'string', 'min:3'],
            'urgency'     => ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.exists' => 'ไม่สามารถแจ้งซ่อมได้ เนื่องจากเครื่องมือนี้ถูกระบุว่าใช้งานไม่ได้แล้ว',
        ];
    }
}
