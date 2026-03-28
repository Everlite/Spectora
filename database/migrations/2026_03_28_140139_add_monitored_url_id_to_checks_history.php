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
        try {
            Schema::table('checks_history', function (Blueprint $table) {
                if (!Schema::hasColumn('checks_history', 'monitored_url_id')) {
                    $table->foreignId('monitored_url_id')->nullable()->after('domain_id')->constrained('monitored_urls')->onDelete('cascade');
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
        Schema::table('checks_history', function (Blueprint $table) {
            $table->dropColumn('monitored_url_id');
        });
    }
};
