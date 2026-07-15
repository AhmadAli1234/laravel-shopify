<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Same fix as shopify_line_item_id (see
 * 2026_07_14_000002_change_shopify_line_item_id_to_string.php): only ever
 * used as an equality match for updateOrCreate's upsert key, never
 * arithmetic or ordering, so it doesn't need to be numeric. Storing it as
 * a string removes the "out of range" possibility on this column while
 * keeping the (order_id, shopify_transaction_id) unique constraint intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_transactions')) {
            return;
        }

        DB::statement('ALTER TABLE `order_transactions` MODIFY `shopify_transaction_id` VARCHAR(64) NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_transactions')) {
            return;
        }

        DB::statement('ALTER TABLE `order_transactions` MODIFY `shopify_transaction_id` BIGINT UNSIGNED NOT NULL');
    }
};
