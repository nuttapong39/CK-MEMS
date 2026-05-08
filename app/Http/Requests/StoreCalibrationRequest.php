<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalibrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'integer', 'exists:equipments,id'],
            'calibrated_at' => ['required', 'date'],
            'next_due_at' => ['nullable', 'date', 'after:calibrated_at'],
            'organization' => ['required', 'string', 'max:255'],
            'calibrator_name' => ['nullable', 'string', 'max:255'],
            'calibrator_phone' => ['nullable', 'string', 'max:32'],
            'controller_name' => ['nullable', 'string', 'max:255'],
            'result' => ['required', Rule::in(['PASS', 'FAIL'])],
            'certificate_no' => ['nullable', 'string', 'max:128'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string'],
        ];
    }
}
