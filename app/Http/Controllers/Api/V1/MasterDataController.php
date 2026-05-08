<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EquipmentCode;
use App\Models\RiskLevel;
use Illuminate\Http\JsonResponse;

class MasterDataController extends Controller
{
    public function departments(): JsonResponse
    {
        return response()->json(
            Department::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name_th', 'name_en'])
        );
    }

    public function equipmentCodes(): JsonResponse
    {
        return response()->json(
            EquipmentCode::with('riskLevel:id,code,name_th,color_hex')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name_th', 'name_en', 'risk_level_id', 'default_calibration_cycle_months'])
        );
    }

    public function riskLevels(): JsonResponse
    {
        return response()->json(
            RiskLevel::orderBy('sort_order')->get()
        );
    }
}
