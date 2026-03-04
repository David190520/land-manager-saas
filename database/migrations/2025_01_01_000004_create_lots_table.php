<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number');
            $table->decimal('area', 10, 2); // in m²
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('front_length', 8, 2)->nullable(); // Frente
            $table->decimal('depth_length', 8, 2)->nullable(); // Fondo
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['block_id', 'lot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
