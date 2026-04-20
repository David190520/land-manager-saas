<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('internal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = all users in tenant
            $table->string('type'); // payment_due_soon, overdue_detected, reservation_pending, contract_expiring, new_client, payment_recorded
            $table->enum('urgency', ['high', 'medium', 'info'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->string('reference_type')->nullable(); // App\Models\Payment, App\Models\Reservation, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('action_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('dedup_key')->nullable()->unique(); // prevents duplicate notifications
            $table->timestamps();

            $table->index(['tenant_id', 'is_read', 'created_at']);
            $table->index(['tenant_id', 'user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notifications');
    }
};
