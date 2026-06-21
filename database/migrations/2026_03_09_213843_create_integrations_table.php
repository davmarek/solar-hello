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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained('integration_providers')
                ->restrictOnDelete();

            $table->string('label')->nullable();                  // User-defined name, e.g. "Garage roof"
            $table->text('credentials');                          // Encrypted JSON via encrypted:array cast
            $table->json('settings')->nullable();                 // Provider-specific config overrides

            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
