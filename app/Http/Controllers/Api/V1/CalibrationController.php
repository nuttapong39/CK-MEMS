<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalibrationRequest;
use App\Http\Resources\CalibrationResource;
use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\FlexMessageTemplate;
use App\Services\MophAlertService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CalibrationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $hospitalId = $request->user()->hospital_id;

        $query = Calibration::with([
            'equipment.department:id,code,name_th',
            'creator:id,name,full_name',
        ])->where('hospital_id', $hospitalId);

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->integer('equipment_id'));
        }
        if ($request->filled('department_id')) {
            $deptId = $request->integer('department_id');
            $query->whereHas('equipment', fn ($q) => $q->where('department_id', $deptId));
        }
        if ($request->filled('result')) {
            $query->where('result', $request->string('result'));
        }
        if ($request->filled('due_within_days')) {
            $days = $request->integer('due_within_days');
            $query->whereBetween('next_due_at', [now()->toDateString(), now()->addDays($days)->toDateString()]);
        }

        $perPage = min($request->integer('per_page', 25), 100);

        return CalibrationResource::collection(
            $query->orderByDesc('calibrated_at')->paginate($perPage),
        );
    }

    public function show(Calibration $calibration, Request $request): CalibrationResource
    {
        abort_if($calibration->hospital_id !== $request->user()->hospital_id, 404);

        $calibration->load(['equipment.department', 'creator']);

        return new CalibrationResource($calibration);
    }

    public function store(StoreCalibrationRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $equipment = Equipment::with('equipmentCode')
            ->where('hospital_id', $user->hospital_id)
            ->findOrFail($data['equipment_id']);

        // Auto-calc next_due_at from equipment_code default cycle if not provided
        if (empty($data['next_due_at'])) {
            $months = $equipment->equipmentCode->default_calibration_cycle_months ?? 12;
            $data['next_due_at'] = Carbon::parse($data['calibrated_at'])->addMonths($months)->toDateString();
        }

        $calibration = Calibration::create([
            ...$data,
            'hospital_id' => $user->hospital_id,
            'created_by' => $user->id,
        ]);

        $calibration->load(['equipment.department', 'creator']);

        app(MophAlertService::class)->notify(
            $user->hospital,
            FlexMessageTemplate::KEY_CALIBRATION_DONE,
            app(MophAlertService::class)->buildCalibrationVariables($calibration),
            'calibration.done:'.$calibration->id,
        );

        return response()->json(new CalibrationResource($calibration), 201);
    }

    public function summary(Request $request): JsonResponse
    {
        $hospitalId = $request->user()->hospital_id;

        $thisYear = Calibration::where('hospital_id', $hospitalId)
            ->whereYear('calibrated_at', now()->year)
            ->distinct('equipment_id')
            ->count('equipment_id');

        $passRate = Calibration::where('hospital_id', $hospitalId)
            ->whereYear('calibrated_at', now()->year)
            ->selectRaw("SUM(result='PASS') AS pass_cnt, COUNT(*) AS total_cnt")
            ->first();

        $dueSoon = Calibration::where('hospital_id', $hospitalId)
            ->whereBetween('next_due_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->distinct('equipment_id')
            ->count('equipment_id');

        $overdue = Calibration::where('hospital_id', $hospitalId)
            ->where('next_due_at', '<', now()->toDateString())
            ->whereNotIn('equipment_id', function ($q) use ($hospitalId) {
                $q->select('equipment_id')->from('calibrations')
                  ->where('hospital_id', $hospitalId)
                  ->where('next_due_at', '>=', now()->toDateString());
            })
            ->distinct('equipment_id')
            ->count('equipment_id');

        return response()->json([
            'this_year' => (int) $thisYear,
            'pass_rate' => $passRate?->total_cnt
                ? round(($passRate->pass_cnt / $passRate->total_cnt) * 100, 1)
                : null,
            'due_soon' => (int) $dueSoon,
            'overdue' => (int) $overdue,
        ]);
    }
}
