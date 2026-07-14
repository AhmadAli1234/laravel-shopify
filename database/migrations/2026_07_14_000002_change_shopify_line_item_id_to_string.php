<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * shopify_line_item_id is only ever used as an equality match for
 * updateOrCreate's upsert key - never arithmetic or ordering - so it
 * doesn't need to be numeric. Storing it as a string removes any
 * possibility of an "out of range" insert on this column regardless of
 * root cause, while keeping the (order_id, shopify_line_item_id) unique
 * constraint intact for correct upsert matching (no duplicate line items
 * on re-sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_line_items')) {
            return;
        }

        DB::statement('ALTER TABLE `order_line_items` MODIFY `shopify_line_item_id` VARCHAR(64) NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_line_items')) {
            return;
        }

        DB::statement('ALTER TABLE `order_line_items` MODIFY `shopify_line_item_id` BIGINT UNSIGNED NOT NULL');
    }
};
