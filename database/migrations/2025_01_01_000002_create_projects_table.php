<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location');
            $table->string('municipality')->nullable();
            $table->string('department')->nullable();
            $table->decimal('total_area', 12, 2)->nullable();
            $table->string('area_unit')->default('m²');
            $table->decimal('price_per_m2', 14, 2)->nullable();
            $table->string('status')->default('active'); // active, paused, completed
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
