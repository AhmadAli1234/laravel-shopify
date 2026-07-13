<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('shopify_transaction_id');
            $table->string('kind')->nullable();
            $table->string('status')->nullable();
            $table->string('gateway')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'shopify_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transactions');
    }
};
