<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->after('id')->constrained('hospitals')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('hospital_id')->constrained('departments')->nullOnDelete();
            $table->string('employee_code', 64)->nullable()->after('department_id');
            $table->string('full_name')->nullable()->after('name');
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('provider_id')->nullable()->unique()->after('phone');
            $table->string('avatar_path')->nullable()->after('provider_id');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index(['hospital_id', 'is_active']);
            $table->index(['hospital_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropForeign(['department_id']);
            $table->dropUnique(['provider_id']);
            $table->dropIndex(['hospital_id', 'is_active']);
            $table->dropIndex(['hospital_id', 'employee_code']);
            $table->dropColumn([
                'hospital_id', 'department_id', 'employee_code', 'full_name',
                'phone', 'provider_id', 'avatar_path', 'is_active', 'last_login_at',
            ]);
        });
    }
};
