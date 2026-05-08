<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qrcode_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('name');
            $table->string('paper_size', 32)->default('A4');
            $table->unsignedSmallInteger('qr_size_mm')->default(40);
            $table->json('fields_to_show');
            $table->longText('layout_html')->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hospital_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qrcode_templates');
    }
};
