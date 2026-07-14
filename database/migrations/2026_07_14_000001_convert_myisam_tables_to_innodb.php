<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Some hosts (seen on a cPanel deployment) default to MyISAM instead of
 * InnoDB for CREATE TABLE. Besides silently ignoring every foreign key
 * constraint this app defines, MyISAM has no crash-safe transactions - an
 * interrupted write (e.g. a migration killed mid-run, which happened
 * several times reaching this schema - see README) can leave its index/
 * data files internally inconsistent with what SHOW CREATE TABLE reports,
 * producing bizarre errors on otherwise-valid inserts.
 *
 * Converting every MyISAM table to InnoDB rebuilds each one from scratch,
 * which both fixes that inconsistency and brings foreign keys to life.
 * config/database.php now also forces 'engine' => 'InnoDB' so this doesn't
 * recur on any table created after this point. Safe/idempotent - if
 * everything is already InnoDB (e.g. locally), this is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = DB::select(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND ENGINE = 'MyISAM'"
        );

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE `{$table->TABLE_NAME}` ENGINE=InnoDB");
        }
    }

    public function down(): void
    {
        // Intentionally left as-is - reverting to MyISAM would just
        // reintroduce the problem this fixes.
    }
};
