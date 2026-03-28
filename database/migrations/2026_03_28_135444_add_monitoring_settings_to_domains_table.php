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
            Schema::table('domains', function (Blueprint $table) {
                $table->boolean('only_check_public_pages')->default(true)->after('url');
                $table->boolean('respect_robots_txt')->default(true)->after('only_check_public_pages');
                $table->boolean('respect_noindex')->default(true)->after('respect_robots_txt');
                $table->text('exclude_patterns')->nullable()->after('respect_noindex');
                $table->json('sitemap_urls')->nullable()->after('exclude_patterns');
                $table->json('included_sitemaps')->nullable()->after('sitemap_urls');
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
            $table->dropColumn([
                'only_check_public_pages',
                'respect_robots_txt',
                'respect_noindex',
                'exclude_patterns',
                'sitemap_urls',
                'included_sitemaps'
            ]);
        });
    }
};
