<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DEPRECATED: The plans table is dropped by convert_out_of_saas migration.
 * This migration is now a no-op for safe migrate:fresh execution.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op: plans table is removed by Agency Edition conversion
    }

    public function down(): void
    {
        // No-op
    }
};
