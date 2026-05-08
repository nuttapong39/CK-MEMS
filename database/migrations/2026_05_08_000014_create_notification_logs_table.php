<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('template_key', 64);
            $table->string('event_signature', 128)->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->index(['hospital_id', 'sent_at']);
            $table->index('template_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
