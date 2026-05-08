<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'equipment_code_id' => ['sometimes', 'integer', 'exists:equipment_codes,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'fiscal_year' => ['sometimes', 'integer', 'between:2500,2700'],
            'asset_number' => ['nullable', 'string', 'max:128'],
            'name_th' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'maintenance_cycles_per_year' => ['sometimes', 'integer', Rule::in([1, 2, 3])],
            'calibration_by' => ['sometimes', Rule::in(['DSS', 'PRIVATE', 'BOTH', 'NONE'])],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'BROKEN', 'UNDER_REPAIR', 'RETIRED', 'PENDING_DISPOSAL'])],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
