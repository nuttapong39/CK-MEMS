<?php

namespace Database\Seeders;

use App\Models\FlexMessageTemplate;
use App\Models\Hospital;
use Illuminate\Database\Seeder;

class FlexMessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::where('code', env('DEFAULT_HOSPITAL_CODE', 'CK'))->firstOrFail();
        $hospitalName = $hospital->name_th;

        $templates = [
            [
                'key' => FlexMessageTemplate::KEY_CREATE_EQUIPMENT,
                'name' => 'แจ้งเตือน — เพิ่มเครื่องมือแพทย์ใหม่',
                'alt_text' => 'มีการเพิ่มเครื่องมือแพทย์ใหม่',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'เพิ่มเครื่องมือใหม่', '{{equipment.name_th}}', 'รหัส {{equipment.id_code}}', '#10B981'),
            ],
            [
                'key' => FlexMessageTemplate::KEY_REPAIR_REQUEST,
                'name' => 'แจ้งเตือน — มีการแจ้งซ่อมใหม่',
                'alt_text' => 'มีการแจ้งซ่อมเครื่องมือแพทย์',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'แจ้งซ่อมเครื่องมือ', '{{equipment.name_th}}', 'อาการ: {{ticket.symptom}}', '#EF4444'),
            ],
            [
                'key' => FlexMessageTemplate::KEY_REPAIR_ACKNOWLEDGED,
                'name' => 'แจ้งเตือน — รับเรื่องซ่อมแล้ว',
                'alt_text' => 'รับเรื่องซ่อมเรียบร้อย',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'รับเรื่องซ่อมแล้ว', '{{equipment.name_th}}', 'หมายเลข {{ticket.ticket_no}}', '#F59E0B'),
            ],
            [
                'key' => FlexMessageTemplate::KEY_REPAIR_IN_PROGRESS,
                'name' => 'แจ้งเตือน — กำลังดำเนินการซ่อม',
                'alt_text' => 'กำลังซ่อมเครื่องมือ',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'กำลังดำเนินการซ่อม', '{{equipment.name_th}}', 'หมายเลข {{ticket.ticket_no}}', '#FBBF24'),
            ],
            [
                'key' => FlexMessageTemplate::KEY_REPAIR_COMPLETED,
                'name' => 'แจ้งเตือน — ซ่อมเสร็จแล้ว',
                'alt_text' => 'ซ่อมเครื่องมือเสร็จสิ้น',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'ซ่อมเสร็จเรียบร้อย', '{{equipment.name_th}}', 'หมายเลข {{ticket.ticket_no}}', '#22C55E'),
            ],
            [
                'key' => FlexMessageTemplate::KEY_CALIBRATION_DONE,
                'name' => 'แจ้งเตือน — สอบเทียบเสร็จสิ้น',
                'alt_text' => 'สอบเทียบเครื่องมือเรียบร้อย',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'สอบเทียบเสร็จสิ้น', '{{equipment.name_th}}', 'ผล: {{calibration.result}}', '#3B82F6'),
            ],
            [
                'key' => FlexMessageTemplate::KEY_CALIBRATION_DUE,
                'name' => 'แจ้งเตือน — ใกล้ครบกำหนดสอบเทียบ',
                'alt_text' => 'ใกล้ครบกำหนดสอบเทียบ',
                'json_payload' => $this->bubbleTemplate($hospitalName, 'ใกล้ครบกำหนดสอบเทียบ', '{{equipment.name_th}}', 'กำหนด {{calibration.next_due_at}}', '#F97316'),
            ],
        ];

        foreach ($templates as $tpl) {
            FlexMessageTemplate::updateOrCreate(
                ['hospital_id' => $hospital->id, 'key' => $tpl['key']],
                $tpl + ['hospital_id' => $hospital->id, 'is_active' => true]
            );
        }

        $this->command->info('Flex templates seeded: '.FlexMessageTemplate::count());
    }

    private function bubbleTemplate(string $hospitalName, string $title, string $primary, string $secondary, string $accent): string
    {
        $payload = [
            'type' => 'flex',
            'altText' => $title,
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                    'backgroundColor' => $accent,
                    'contents' => [[
                        'type' => 'text', 'text' => $title, 'color' => '#FFFFFF',
                        'weight' => 'bold', 'size' => 'lg', 'align' => 'center',
                    ]],
                ],
                'body' => [
                    'type' => 'box', 'layout' => 'vertical', 'spacing' => 'md', 'paddingAll' => 'lg',
                    'contents' => [
                        ['type' => 'text', 'text' => $hospitalName, 'weight' => 'bold', 'size' => 'md', 'align' => 'center'],
                        ['type' => 'separator', 'margin' => 'md'],
                        ['type' => 'text', 'text' => $primary, 'wrap' => true, 'size' => 'md', 'margin' => 'md'],
                        ['type' => 'text', 'text' => $secondary, 'wrap' => true, 'size' => 'sm', 'color' => '#6B7280'],
                    ],
                ],
            ],
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
