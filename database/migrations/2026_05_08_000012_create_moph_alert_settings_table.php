<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moph_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->unique()->constrained('hospitals')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('endpoint_url')->default('https://morpromt2f.moph.go.th/api/notify/send');
            $table->string('client_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->boolean('notify_on_create_equipment')->default(true);
            $table->boolean('notify_on_repair_request')->default(true);
            $table->boolean('notify_on_repair_progress')->default(true);
            $table->boolean('notify_on_calibration')->default(true);
            $table->timestamp('last_test_at')->nullable();
            $table->string('last_test_status', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moph_alert_settings');
    }
};
