<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('ticket_no', 32);
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->timestamp('reported_at');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();

            $table->text('symptom');
            $table->enum('urgency', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('MEDIUM');
            $table->enum('status', [
                'PENDING', 'ACKNOWLEDGED', 'IN_PROGRESS', 'WAITING_PARTS',
                'REPAIRED', 'CLOSED', 'CANCELLED',
            ])->default('PENDING');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->text('root_cause')->nullable();
            $table->text('action_taken')->nullable();
            $table->text('parts_used')->nullable();
            $table->decimal('repair_cost', 12, 2)->nullable();

            $table->timestamps();

            $table->unique(['hospital_id', 'ticket_no']);
            $table->index(['hospital_id', 'status', 'urgency']);
            $table->index('equipment_id');
            $table->index('reported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_tickets');
    }
};
