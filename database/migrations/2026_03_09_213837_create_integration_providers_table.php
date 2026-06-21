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
        Schema::create('integration_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // 'solax', 'shelly', etc.
            $table->string('display_name');            // 'Solax Cloud'
            $table->string('handler_class');           // FQCN of provider class
            $table->json('config_schema')->nullable(); // Credentials form definition
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_providers');
    }
};
