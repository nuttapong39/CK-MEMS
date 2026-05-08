<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentCode;
use App\Models\Hospital;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedFromExcel extends Command
{
    protected $signature = 'ckmems:seed-from-excel
                            {--file= : Path to seed data folder, default database/data/seed}
                            {--hospital= : Hospital code, default DEFAULT_HOSPITAL_CODE}';

    protected $description = 'Import equipment records from the prepared Excel JSON dumps';

    public function handle(): int
    {
        $base = $this->option('file') ?: database_path('data/seed');
        $equipmentsPath = $base.'/equipments.json';

        if (! file_exists($equipmentsPath)) {
            $this->error("Not found: $equipmentsPath");
            return self::FAILURE;
        }

        $hospitalCode = $this->option('hospital') ?: env('DEFAULT_HOSPITAL_CODE', 'CK');
        $hospital = Hospital::where('code', $hospitalCode)->first();
        if (! $hospital) {
            $this->error("Hospital not found: $hospitalCode (run db:seed first)");
            return self::FAILURE;
        }

        $rows = json_decode(file_get_contents($equipmentsPath), true);
        $this->info(sprintf('Importing %d equipment rows for %s...', count($rows), $hospital->name_th));

        $departments = Department::pluck('id', 'code');
        $codes = EquipmentCode::pluck('id', 'code');

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $deptCode = trim($row['dept'] ?? '');
                $idCode = trim($row['id_code'] ?? '');
                $nameTh = trim($row['name_th'] ?? '');

                if ($nameTh === '') {
                    $skipped++; continue;
                }

                $deptId = $departments[$deptCode] ?? null;
                if (! $deptId) {
                    $errors[] = "row $i: unknown dept '$deptCode'";
                    $skipped++; continue;
                }

                // Resolve equipment_code from the id_code prefix (CM-DEPT-CODE-NN)
                $codeKey = null;
                if ($idCode && preg_match('/^[A-Z]+-[A-Z]+-([A-Z]+)-?\d*$/', $idCode, $m)) {
                    $codeKey = $m[1];
                }
                if (! $codeKey) {
                    // fall back to matching by Thai name's first chars in equipment_codes
                    $codeKey = $this->guessCodeKey($nameTh, $codes->keys()->all());
                }
                $equipmentCodeId = $codes[$codeKey] ?? null;
                if (! $equipmentCodeId) {
                    $errors[] = "row $i: unmapped code (idCode=$idCode, name=$nameTh)";
                    $skipped++; continue;
                }

                // Generate id_code if missing
                if ($idCode === '') {
                    $idCode = sprintf('%s-%s-%s-AUTO%04d',
                        strtoupper($hospital->code), $deptCode, $codeKey, $i + 1);
                }

                Equipment::updateOrCreate(
                    ['hospital_id' => $hospital->id, 'id_code' => $idCode],
                    [
                        'department_id' => $deptId,
                        'equipment_code_id' => $equipmentCodeId,
                        'fiscal_year' => 2569,
                        'asset_number' => $row['asset_number'] ?: null,
                        'name_th' => $nameTh,
                        'name_en' => $row['name_en'] ?: null,
                        'manufacturer' => $row['manufacturer'] ?: null,
                        'model' => $row['model'] ?: null,
                        'serial_number' => $row['serial_number'] ?: null,
                        'note' => $row['note'] ?: null,
                        'status' => $this->inferStatus($row['note'] ?? ''),
                    ]
                );
                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import aborted: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info("Imported : $imported");
        $this->warn("Skipped  : $skipped");
        if (! empty($errors)) {
            $this->warn('First 10 issues:');
            foreach (array_slice($errors, 0, 10) as $e) $this->line('  - '.$e);
        }

        return self::SUCCESS;
    }

    private function inferStatus(string $note): string
    {
        $n = mb_strtolower($note);
        if (str_contains($n, 'ยกเลิก') || str_contains($n, 'แทงจำหน่าย')) return 'PENDING_DISPOSAL';
        if (str_contains($n, 'ชำรุด')) return 'BROKEN';
        return 'ACTIVE';
    }

    private function guessCodeKey(string $nameTh, array $availableCodes): ?string
    {
        // crude heuristic: pick the longest equipment_code whose name partially matches
        // (will be overridden when id_code is present)
        return null;
    }
}
