<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('domains')) {
            return;
        }

        try {
            Schema::table('domains', function (Blueprint $table) {
                if (!Schema::hasColumn('domains', 'safety_details')) {
                    $table->json('safety_details')->nullable()->after('safety_status');
                }
                if (!Schema::hasColumn('domains', 'visitors_today')) {
                    $table->integer('visitors_today')->default(0)->after('response_time');
                }
            });
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'duplicate column name')) {
                throw $e;
            }
        }

        if (Schema::hasTable('checks_history')) {
            Schema::table('checks_history', function (Blueprint $table) {
                if (!Schema::hasColumn('checks_history', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('domains')) {
            return;
        }

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['safety_details', 'visitors_today']);
        });

        Schema::table('checks_history', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
