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
        Schema::create('available_power_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete(); // Denormalized

            $table->timestamp('calculated_at');

            // Sum of (producer.grid_power_w clamped to 0 * allocation_percentage / 100)
            // across all source places that have an allocation targeting this place.
            // Always >= 0.
            $table->float('allocated_w');

            // Current consumption of this place. Null if no consumption integration exists.
            // For Solax places: house_load_w from the latest energy reading.
            // For Shelly places: abs(grid_power_w) from the latest energy reading.
            $table->float('consumption_w')->nullable();

            // allocated_w - consumption_w.
            // Null if consumption_w is null — we don't know actual consumption.
            // Can be negative if the place is consuming more than allocated.
            $table->float('net_w')->nullable();

            // True when consumption_w is null (no consumption integration).
            // Signals to the UI that allocated_w is the ceiling but actual
            // free power is unknown — shown as an estimate to the user.
            $table->boolean('is_estimate');

            $table->timestamps();

            $table->index(['place_id', 'calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_power_snapshots');
    }
};
