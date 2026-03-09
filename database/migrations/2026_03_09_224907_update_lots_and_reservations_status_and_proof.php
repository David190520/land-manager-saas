<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('payment_proof')->nullable();
        });

        DB::statement("ALTER TABLE lots DROP CONSTRAINT IF EXISTS lots_status_check");
        DB::statement("ALTER TABLE lots ADD CONSTRAINT lots_status_check CHECK (status::text = ANY (ARRAY['available'::character varying, 'reserved'::character varying, 'sold'::character varying, 'pending_approval'::character varying]::text[]))");

        DB::statement("ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_status_check");
        DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_status_check CHECK (status::text = ANY (ARRAY['active'::character varying, 'confirmed'::character varying, 'expired'::character varying, 'cancelled'::character varying, 'pending_approval'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });
    }
};
