<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\CalibrationsExport;
use App\Exports\EquipmentsExport;
use App\Exports\RepairsExport;
use App\Http\Controllers\Controller;
use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\RepairTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function equipmentsExcel(Request $request)
    {
        $filters = $request->only(['department_id', 'status', 'fiscal_year']);
        $hospitalId = $request->user()->hospital_id;
        $filename = sprintf('equipments_%s.xlsx', now()->format('Ymd_His'));

        return Excel::download(
            new EquipmentsExport($hospitalId, $filters),
            $filename,
            ExcelType::XLSX,
        );
    }

    public function equipmentsPdf(Request $request)
    {
        $hospitalId = $request->user()->hospital_id;

        $q = Equipment::with('department:id,code,name_th', 'equipmentCode:id,code')
            ->where('hospital_id', $hospitalId);

        if ($request->filled('department_id')) $q->where('department_id', $request->integer('department_id'));
        if ($request->filled('status'))         $q->where('status', $request->string('status'));
        if ($request->filled('fiscal_year'))    $q->where('fiscal_year', $request->integer('fiscal_year'));

        $items = $q->orderBy('id_code')->limit(2000)->get();

        $pdf = Pdf::loadView('pdf.equipments-list', [
            'items' => $items,
            'hospital' => $request->user()->hospital,
            'generated_at' => now(),
            'filters' => $request->only(['department_id', 'status', 'fiscal_year']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(sprintf('equipments_%s.pdf', now()->format('Ymd_His')));
    }

    public function repairsExcel(Request $request)
    {
        $hospitalId = $request->user()->hospital_id;
        $filename = sprintf('repairs_%s.xlsx', now()->format('Ymd_His'));

        return Excel::download(
            new RepairsExport($hospitalId, $request->input('from'), $request->input('to'), $request->input('status')),
            $filename,
            ExcelType::XLSX,
        );
    }

    public function repairsPdf(Request $request)
    {
        $hospitalId = $request->user()->hospital_id;

        $q = RepairTicket::with('equipment.department:id,code,name_th', 'reporter:id,name,full_name')
            ->where('hospital_id', $hospitalId);

        if ($request->filled('from')) $q->where('reported_at', '>=', $request->input('from'));
        if ($request->filled('to'))   $q->where('reported_at', '<=', $request->input('to').' 23:59:59');
        if ($request->filled('status')) $q->where('status', $request->string('status'));

        $items = $q->orderByDesc('reported_at')->limit(2000)->get();

        $pdf = Pdf::loadView('pdf.repairs-list', [
            'items' => $items,
            'hospital' => $request->user()->hospital,
            'generated_at' => now(),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(sprintf('repairs_%s.pdf', now()->format('Ymd_His')));
    }

    public function calibrationsExcel(Request $request)
    {
        $hospitalId = $request->user()->hospital_id;
        $filename = sprintf('calibrations_%s.xlsx', now()->format('Ymd_His'));

        return Excel::download(
            new CalibrationsExport($hospitalId, $request->input('from'), $request->input('to')),
            $filename,
            ExcelType::XLSX,
        );
    }

    public function calibrationCertificate(Calibration $calibration, Request $request)
    {
        abort_if($calibration->hospital_id !== $request->user()->hospital_id, 404);

        $calibration->load(['equipment.department', 'creator']);

        $pdf = Pdf::loadView('pdf.calibration-certificate', [
            'cal' => $calibration,
            'hospital' => $request->user()->hospital,
        ])->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('certificate_%s_%s.pdf', $calibration->equipment->id_code, $calibration->id));
    }
}
