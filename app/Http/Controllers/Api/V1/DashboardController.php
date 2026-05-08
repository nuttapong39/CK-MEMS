<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\RepairTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $hospitalId = $request->user()->hospital_id;

        $total = Equipment::where('hospital_id', $hospitalId)->count();

        $byStatus = Equipment::where('hospital_id', $hospitalId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $disposed = (int) $byStatus->get('PENDING_DISPOSAL', 0) + (int) $byStatus->get('RETIRED', 0);
        $active = (int) $byStatus->get('ACTIVE', 0);
        $broken = (int) $byStatus->get('BROKEN', 0) + (int) $byStatus->get('UNDER_REPAIR', 0);

        $calibratedCount = Calibration::where('hospital_id', $hospitalId)
            ->whereYear('calibrated_at', '>=', now()->year - 1)
            ->distinct('equipment_id')
            ->count('equipment_id');

        $openRepairs = RepairTicket::where('hospital_id', $hospitalId)
            ->whereNotIn('status', ['CLOSED', 'CANCELLED'])
            ->count();

        $dueSoon = Calibration::where('hospital_id', $hospitalId)
            ->whereBetween('next_due_at', [now(), now()->addDays(30)])
            ->distinct('equipment_id')
            ->count('equipment_id');

        return response()->json([
            'hero' => [
                'label' => 'เครื่องมือทั้งหมด',
                'value' => $total,
                'unit' => 'รายการ',
            ],
            'cards' => [
                ['key' => 'active', 'label' => 'ใช้งานอยู่', 'value' => $active, 'color' => 'emerald'],
                ['key' => 'broken', 'label' => 'ชำรุด/กำลังซ่อม', 'value' => $broken, 'color' => 'rose'],
                ['key' => 'disposed', 'label' => 'จำหน่าย/รอแทงจำหน่าย', 'value' => $disposed, 'color' => 'slate'],
                ['key' => 'calibrated', 'label' => 'สอบเทียบแล้ว (1 ปีล่าสุด)', 'value' => $calibratedCount, 'color' => 'blue'],
                ['key' => 'due_soon', 'label' => 'ใกล้ครบสอบเทียบ (30 วัน)', 'value' => $dueSoon, 'color' => 'amber'],
                ['key' => 'open_repairs', 'label' => 'งานซ่อมค้าง', 'value' => $openRepairs, 'color' => 'violet'],
            ],
        ]);
    }
}
