<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/seed/departments.json');
        $rows = json_decode(file_get_contents($path), true);

        foreach ($rows as $row) {
            Department::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name_th' => $row['name_th'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Departments seeded: '.Department::count());
    }
}
