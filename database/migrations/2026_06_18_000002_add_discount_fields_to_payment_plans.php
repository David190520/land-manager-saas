<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('none')->after('initial_payment_date');
            $table->decimal('discount_value', 14, 2)->default(0)->after('discount_type');
            $table->decimal('original_price', 14, 2)->nullable()->after('discount_value');
        });

        // For existing plans: no discount was applied, original_price = total_price
        DB::statement('UPDATE payment_plans SET original_price = total_price WHERE original_price IS NULL');
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'original_price']);
        });
    }
};
