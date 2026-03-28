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
        if (!Schema::hasTable('domains')) {
            return;
        }

        try {
            Schema::table('domains', function (Blueprint $table) {
                if (!Schema::hasColumn('domains', 'last_pagespeed_details')) {
                    $table->json('last_pagespeed_details')->nullable()->after('pagespeed_desktop');
                }
            });
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'duplicate column name')) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('last_pagespeed_details');
        });
    }
};
