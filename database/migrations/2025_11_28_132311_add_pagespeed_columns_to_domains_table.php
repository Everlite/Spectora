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
                if (!Schema::hasColumn('domains', 'pagespeed_score')) {
                    $table->integer('pagespeed_score')->nullable()->after('safety_status');
                }
                if (!Schema::hasColumn('domains', 'pagespeed_score_desktop')) {
                    $table->integer('pagespeed_score_desktop')->nullable()->after('pagespeed_score');
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
            $table->dropColumn(['pagespeed_mobile', 'pagespeed_desktop']);
        });
    }
};
