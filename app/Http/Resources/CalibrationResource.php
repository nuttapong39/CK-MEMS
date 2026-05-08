<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalibrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'calibrated_at' => $this->calibrated_at,
            'next_due_at' => $this->next_due_at,
            'organization' => $this->organization,
            'calibrator_name' => $this->calibrator_name,
            'calibrator_phone' => $this->calibrator_phone,
            'controller_name' => $this->controller_name,
            'result' => $this->result,
            'certificate_no' => $this->certificate_no,
            'cost' => $this->cost,
            'remark' => $this->remark,
            'attachment_path' => $this->attachment_path,
            'attachment_url' => $this->attachment_path ? asset('storage/'.$this->attachment_path) : null,
            'equipment' => $this->whenLoaded('equipment', fn () => $this->equipment ? [
                'id' => $this->equipment->id,
                'id_code' => $this->equipment->id_code,
                'name_th' => $this->equipment->name_th,
                'manufacturer' => $this->equipment->manufacturer,
                'model' => $this->equipment->model,
                'department' => $this->equipment->department ? [
                    'id' => $this->equipment->department->id,
                    'code' => $this->equipment->department->code,
                    'name_th' => $this->equipment->department->name_th,
                ] : null,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'full_name' => $this->creator->full_name ?? $this->creator->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
