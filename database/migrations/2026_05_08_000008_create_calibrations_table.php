<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();

            $table->date('calibrated_at');
            $table->date('next_due_at')->nullable();

            $table->string('organization');
            $table->string('calibrator_name')->nullable();
            $table->string('calibrator_phone', 32)->nullable();
            $table->string('controller_name')->nullable();

            $table->enum('result', ['PASS', 'FAIL'])->default('PASS');
            $table->string('certificate_no')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('remark')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['equipment_id', 'calibrated_at']);
            $table->index(['hospital_id', 'next_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibrations');
    }
};
