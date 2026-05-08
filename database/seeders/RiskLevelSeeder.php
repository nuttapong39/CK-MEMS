<?php

namespace Database\Seeders;

use App\Models\RiskLevel;
use Illuminate\Database\Seeder;

class RiskLevelSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'HIGH',    'name_th' => 'ความเสี่ยงสูง',  'sort_order' => 1, 'color_hex' => '#DC2626', 'recommended_calibration_months' => 12, 'description' => 'เครื่องมือมีความเสี่ยงสูง 7 รายการ ต้องสอบเทียบ'],
            ['code' => 'MEDIUM',  'name_th' => 'ความเสี่ยงปานกลาง', 'sort_order' => 2, 'color_hex' => '#F59E0B', 'recommended_calibration_months' => 12, 'description' => 'รายการรอง สอบเทียบตามความเหมาะสม'],
            ['code' => 'LOW',     'name_th' => 'ความเสี่ยงต่ำ (60%)',  'sort_order' => 3, 'color_hex' => '#2563EB', 'recommended_calibration_months' => 24, 'description' => 'รายการเครื่องมือแพทย์อื่นๆ (60%)'],
            ['code' => 'MINIMAL', 'name_th' => 'พิจารณาตามความจำเป็น',  'sort_order' => 4, 'color_hex' => '#94A3B8', 'recommended_calibration_months' => 24, 'description' => 'พิจารณาตามความจำเป็นและเหมาะสม'],
        ];

        foreach ($rows as $row) {
            RiskLevel::updateOrCreate(['code' => $row['code']], $row);
        }

        $this->command->info('Risk levels seeded: '.RiskLevel::count());
    }
}
