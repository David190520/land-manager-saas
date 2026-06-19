<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->unsignedBigInteger('seller_id');
            $table->foreign('seller_id')->references('id')->on('sellers');
            $table->unsignedBigInteger('reservation_id');
            $table->foreign('reservation_id')->references('id')->on('reservations');
            $table->unsignedBigInteger('payment_plan_id');
            $table->foreign('payment_plan_id')->references('id')->on('payment_plans');
            $table->decimal('base_amount', 14, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 14, 2);
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('seller_id');
        });

        DB::statement("ALTER TABLE commissions ADD CONSTRAINT commissions_status_check CHECK (status IN ('pending', 'paid'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
