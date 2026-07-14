<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A deployment attempt interrupted mid-migration by unrelated errors (a
 * pre-existing 'users' table, then a MySQL index-key-length failure - see
 * README) left several shopify_*_id columns as plain INT on at least one
 * environment - too small for real Shopify IDs (e.g. 35758254817647 for a
 * line item). The create-table migrations already declare these correctly
 * as unsignedBigInteger, but a migration already marked as run is never
 * re-applied, so this explicitly corrects the column on any environment
 * where it's still wrong. Safe/idempotent to run anywhere, including
 * environments where the type is already correct.
 *
 * Raw SQL (not Schema::table()->change()) since doctrine/dbal isn't
 * installed in this project - MODIFY COLUMN doesn't need it and preserves
 * any existing index/unique constraint already on the column.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'products' => ['shopify_product_id' => 'NOT NULL'],
        'product_variants' => [
            'shopify_variant_id' => 'NOT NULL',
            'shopify_inventory_item_id' => 'NULL',
        ],
        'inventory_levels' => ['shopify_location_id' => 'NOT NULL'],
        'customers' => ['shopify_customer_id' => 'NOT NULL'],
        'collections' => ['shopify_collection_id' => 'NOT NULL'],
        'orders' => ['shopify_order_id' => 'NOT NULL'],
        'order_line_items' => ['shopify_line_item_id' => 'NOT NULL'],
        'fulfillments' => ['shopify_fulfillment_id' => 'NOT NULL'],
        'order_transactions' => ['shopify_transaction_id' => 'NOT NULL'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $nullability) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED {$nullability}");
            }
        }
    }

    public function down(): void
    {
        // Intentionally left as-is - reverting to a too-small column type
        // would just reintroduce the bug this fixes.
    }
};
