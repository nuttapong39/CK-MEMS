<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flex_message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('name');
            $table->string('alt_text')->default('แจ้งเตือนจากโรงพยาบาล');
            $table->longText('json_payload');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['hospital_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flex_message_templates');
    }
};
