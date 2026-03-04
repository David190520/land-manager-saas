<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_price', 14, 2);
            $table->decimal('down_payment', 14, 2);
            $table->decimal('financed_amount', 14, 2); // total_price - down_payment
            $table->integer('total_installments');
            $table->decimal('installment_amount', 14, 2); // financed / installments
            $table->decimal('interest_rate', 5, 2)->default(0); // 0% per requirement
            $table->date('start_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
