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
        if (!Schema::hasColumn('checks_history', 'checked_at')) {
            Schema::table('checks_history', function (Blueprint $table) {
                $table->timestamp('checked_at')->nullable()->after('domain_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checks_history', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });
    }
};
