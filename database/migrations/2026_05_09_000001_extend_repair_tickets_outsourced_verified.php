<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alter the status enum to include OUTSOURCED and VERIFIED
        DB::statement("ALTER TABLE repair_tickets MODIFY COLUMN status ENUM(
            'PENDING','ACKNOWLEDGED','IN_PROGRESS','WAITING_PARTS',
            'OUTSOURCED','REPAIRED','VERIFIED','CLOSED','CANCELLED'
        ) NOT NULL DEFAULT 'PENDING'");

        Schema::table('repair_tickets', function (Blueprint $table) {
            // SLA target timestamp (set on create based on urgency)
            $table->timestamp('sla_due_at')->nullable()->after('reported_at');

            // Outsource fields
            $table->string('vendor_name')->nullable()->after('repair_cost');
            $table->string('outsource_ref')->nullable()->after('vendor_name');
            $table->timestamp('outsourced_at')->nullable()->after('outsource_ref');
            $table->timestamp('expected_return_at')->nullable()->after('outsourced_at');

            // Verified (reporter confirms receipt)
            $table->timestamp('verified_at')->nullable()->after('expected_return_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'sla_due_at', 'vendor_name', 'outsource_ref',
                'outsourced_at', 'expected_return_at', 'verified_at', 'verified_by',
            ]);
        });

        DB::statement("ALTER TABLE repair_tickets MODIFY COLUMN status ENUM(
            'PENDING','ACKNOWLEDGED','IN_PROGRESS','WAITING_PARTS',
            'REPAIRED','CLOSED','CANCELLED'
        ) NOT NULL DEFAULT 'PENDING'");
    }
};
