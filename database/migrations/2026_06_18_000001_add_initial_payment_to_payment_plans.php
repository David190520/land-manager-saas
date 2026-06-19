<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the status check constraint to include the new status
        DB::statement("ALTER TABLE payment_plans DROP CONSTRAINT IF EXISTS payment_plans_status_check");
        DB::statement("ALTER TABLE payment_plans ADD CONSTRAINT payment_plans_status_check CHECK (status IN ('active', 'completed', 'cancelled', 'pending_initial_payment'))");

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->decimal('initial_payment_percentage', 5, 2)->default(30)->after('status');
            $table->decimal('initial_payment_amount', 14, 2)->nullable()->after('initial_payment_percentage');
            $table->date('initial_payment_deadline')->nullable()->after('initial_payment_amount');
            $table->boolean('initial_payment_paid')->default(false)->after('initial_payment_deadline');
            $table->date('initial_payment_date')->nullable()->after('initial_payment_paid');
        });

        // Pre-existing plans have already completed their initial payment phase
        DB::statement("UPDATE payment_plans SET initial_payment_paid = true WHERE status IN ('active', 'completed')");
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'initial_payment_percentage',
                'initial_payment_amount',
                'initial_payment_deadline',
                'initial_payment_paid',
                'initial_payment_date',
            ]);
        });

        DB::statement("ALTER TABLE payment_plans DROP CONSTRAINT IF EXISTS payment_plans_status_check");
        DB::statement("ALTER TABLE payment_plans ADD CONSTRAINT payment_plans_status_check CHECK (status IN ('active', 'completed', 'cancelled'))");
    }
};
