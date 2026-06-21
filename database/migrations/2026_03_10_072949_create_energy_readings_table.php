<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('place_id')->constrained()->cascadeOnDelete(); // Denormalized
            $table->foreignId('group_id')->constrained()->cascadeOnDelete(); // Denormalized

            $table->timestamp('recorded_at');

            // Power Flows =========================================================
            // What the inverter is outputting as AC — the primary production metric
            $table->float('inverter_power_w')->nullable();

            // Grid exchange: positive = export (selling), negative = import (buying)
            // This is your "power available to the group" field
            $table->float('grid_power_w')->nullable();

            // Battery flow: positive = charging, negative = discharging
            // null if no battery installed
            $table->float('battery_power_w')->nullable();

            // Battery state of charge as a percentage (0–100)
            // null if no battery installed
            $table->float('battery_soc_pct')->nullable();

            // Energy Counters ===================================
            $table->float('energy_today_kwh')->nullable();
            $table->float('energy_total_kwh')->nullable();

            $table->json('raw_payload')->nullable(); // Original provider response — never discard

            $table->timestamps();

            // Deduplication guard: prevents the same inverter snapshot from being
            // inserted twice when the scheduler and the inverter upload cadence overlap.
            // SyncService::persist() uses firstOrCreate on these same two columns.
            $table->unique(['integration_id', 'recorded_at']);

            $table->index(['place_id', 'recorded_at']);
            $table->index(['group_id', 'recorded_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_readings');
    }
};
