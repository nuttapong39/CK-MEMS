<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\MophAlertSetting;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::updateOrCreate(
            ['code' => env('DEFAULT_HOSPITAL_CODE', 'CK')],
            [
                'name_th' => env('DEFAULT_HOSPITAL_NAME', 'โรงพยาบาลเชียงกลาง'),
                'name_en' => 'Chiang Klang Hospital',
                'province' => 'น่าน',
                'district' => 'เชียงกลาง',
                'is_active' => true,
            ]
        );

        MophAlertSetting::firstOrCreate(
            ['hospital_id' => $hospital->id],
            [
                'is_enabled' => false,
                'endpoint_url' => env('MOPH_ALERT_URL', 'https://morpromt2f.moph.go.th/api/notify/send'),
                'client_key' => env('MOPH_ALERT_CLIENT_KEY'),
                'secret_key' => env('MOPH_ALERT_SECRET_KEY'),
            ]
        );

        $this->command->info("Hospital seeded: {$hospital->code} - {$hospital->name_th}");
    }
}
