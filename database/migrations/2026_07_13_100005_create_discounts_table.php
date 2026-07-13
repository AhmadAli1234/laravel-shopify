<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('users')->cascadeOnDelete();
            // Discounts have no legacy numeric REST id in Shopify's API - the
            // GraphQL global id (gid://shopify/DiscountCodeNode/123) is the
            // only stable identifier, so it's stored in full rather than
            // extracted to an integer like every other synced entity.
            $table->string('shopify_discount_gid');
            $table->string('discount_type')->nullable();
            $table->string('title')->nullable();
            $table->string('code')->nullable();
            $table->string('status')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'shopify_discount_gid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
