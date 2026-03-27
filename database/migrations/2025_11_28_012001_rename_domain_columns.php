<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('domains', 'status_code')) {
                $table->integer('status_code')->nullable()->after('url');
            }
            if (!Schema::hasColumn('domains', 'response_time')) {
                $table->decimal('response_time', 8, 4)->nullable()->after('ssl_days_left');
            }
        });

        // Copy data safely
        if (Schema::hasColumn('domains', 'last_status') && Schema::hasColumn('domains', 'status_code')) {
            DB::statement('UPDATE domains SET status_code = last_status WHERE last_status IS NOT NULL');
        }
        if (Schema::hasColumn('domains', 'last_response_time') && Schema::hasColumn('domains', 'response_time')) {
            DB::statement('UPDATE domains SET response_time = last_response_time WHERE last_response_time IS NOT NULL');
        }

        Schema::table('domains', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('domains', 'last_status')) {
                $table->dropColumn('last_status');
            }
            if (Schema::hasColumn('domains', 'last_response_time')) {
                $table->dropColumn('last_response_time');
            }
            
            // Handle renames if columns exist
            if (Schema::hasColumn('domains', 'last_response_time') && !Schema::hasColumn('domains', 'response_time')) {
                $table->renameColumn('last_response_time', 'response_time');
            }
            if (Schema::hasColumn('domains', 'last_ip') && !Schema::hasColumn('domains', 'ip_address')) {
                $table->renameColumn('last_ip', 'ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            if (!Schema::hasColumn('domains', 'last_status')) {
                $table->string('last_status')->nullable();
            }
            if (!Schema::hasColumn('domains', 'last_response_time')) {
                $table->decimal('last_response_time', 6, 3)->nullable();
            }
        });
    }
};
