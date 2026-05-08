<?php

namespace Database\Seeders;

use App\Models\EquipmentCode;
use App\Models\RiskLevel;
use Illuminate\Database\Seeder;

class EquipmentCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codesPath = database_path('data/seed/equipment_codes.json');
        $rows = json_decode(file_get_contents($codesPath), true);

        $riskPath = database_path('data/seed/risk_assignments.json');
        $riskMap = json_decode(file_get_contents($riskPath), true);

        $riskLevels = RiskLevel::pluck('id', 'code');

        foreach ($rows as $row) {
            $code = $row['code'];
            $riskCode = $riskMap[$code] ?? 'MINIMAL';
            $riskId = $riskLevels[$riskCode] ?? null;

            EquipmentCode::updateOrCreate(
                ['code' => $code],
                [
                    'name_th' => $row['name_th'],
                    'name_en' => $row['name_en'] ?? null,
                    'risk_level_id' => $riskId,
                    'default_calibration_cycle_months' => $riskCode === 'HIGH' ? 12 : ($riskCode === 'MEDIUM' ? 12 : 24),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Equipment codes seeded: '.EquipmentCode::count());
    }
}
