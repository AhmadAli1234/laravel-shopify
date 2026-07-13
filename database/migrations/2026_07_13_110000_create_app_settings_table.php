<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton table (a single row, always id=1) holding the Shopify
        // app credentials, editable via the Settings page instead of .env.
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shopify_api_key')->nullable();
            $table->string('shopify_api_secret')->nullable();
            $table->string('shopify_api_scopes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
