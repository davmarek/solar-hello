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
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('target_place_id')->constrained('places')->cascadeOnDelete();
            $table->unsignedTinyInteger('percentage');
            $table->timestamps();

            $table->unique(['source_place_id', 'target_place_id']);
            $table->index('source_place_id');
            $table->index('target_place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
