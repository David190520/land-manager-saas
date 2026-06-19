<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop constraint first so the UPDATE is not blocked by the old allowed values
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        DB::statement("UPDATE users SET role = 'secretary' WHERE role = 'sales_agent'");

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'accountant', 'secretary'))");

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'accountant', 'sales_agent'))");

        DB::statement("UPDATE users SET role = 'sales_agent' WHERE role = 'secretary'");
    }
};
