<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentCode;
use App\Models\Hospital;
use Illuminate\Support\Facades\DB;

class EquipmentIdGenerator
{
    public function __construct(private readonly Hospital $hospital)
    {
    }

    /**
     * Build a CM-{DEPT}-{EQUIP}-{SEQ} style id_code unique per hospital.
     * Pattern follows the convention used in โรงพยาบาลเชียงกลาง's existing register.
     */
    public function generate(Department $department, EquipmentCode $code, ?string $prefix = null): string
    {
        $prefix = $prefix ?? strtoupper($this->hospital->code);
        $base = sprintf('%s-%s-%s', $prefix, $department->code, $code->code);

        return DB::transaction(function () use ($base) {
            $latest = Equipment::where('hospital_id', $this->hospital->id)
                ->where('id_code', 'like', $base.'-%')
                ->orderByDesc('id_code')
                ->lockForUpdate()
                ->value('id_code');

            $next = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest, $m)) {
                $next = (int) $m[1] + 1;
            }

            return sprintf('%s-%02d', $base, $next);
        });
    }

    public function preview(Department $department, EquipmentCode $code, ?string $prefix = null): string
    {
        return $this->generate($department, $code, $prefix);
    }
}
