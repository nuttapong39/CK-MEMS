<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('equipment_code_id')->constrained('equipment_codes')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->string('asset_number', 128)->nullable();
            $table->string('id_code', 64);
            $table->string('name_th');
            $table->string('name_en')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            $table->unsignedTinyInteger('maintenance_cycles_per_year')->default(1);
            $table->enum('calibration_by', ['DSS', 'PRIVATE', 'BOTH', 'NONE'])->default('NONE');
            $table->enum('status', ['ACTIVE', 'BROKEN', 'UNDER_REPAIR', 'RETIRED', 'PENDING_DISPOSAL'])->default('ACTIVE');

            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->date('warranty_until')->nullable();

            $table->text('note')->nullable();
            $table->json('qr_payload')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['hospital_id', 'id_code']);
            $table->index(['hospital_id', 'status']);
            $table->index(['hospital_id', 'department_id']);
            $table->index('fiscal_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
