<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE equipments MODIFY COLUMN status ENUM(
            'ACTIVE','BROKEN','UNDER_REPAIR','RETIRED','PENDING_DISPOSAL','OUT_OF_SERVICE'
        ) NOT NULL DEFAULT 'ACTIVE'");

        Schema::table('equipments', function (Blueprint $table) {
            $table->timestamp('decommissioned_at')->nullable()->after('note');
            $table->text('decommissioned_reason')->nullable()->after('decommissioned_at');
        });
    }

    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn(['decommissioned_at', 'decommissioned_reason']);
        });
        DB::statement("ALTER TABLE equipments MODIFY COLUMN status ENUM(
            'ACTIVE','BROKEN','UNDER_REPAIR','RETIRED','PENDING_DISPOSAL'
        ) NOT NULL DEFAULT 'ACTIVE'");
    }
};
