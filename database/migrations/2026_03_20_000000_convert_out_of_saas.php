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
        Schema::disableForeignKeyConstraints();

        // Drop SaaS tables
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('domain_deletion_log');
        Schema::dropIfExists('stripe_events');

        Schema::enableForeignKeyConstraints();

        // Clean User table
        Schema::table('users', function (Blueprint $table) {
            // Drop SaaS columns if they exist
            if (Schema::hasColumn('users', 'stripe_customer_id')) {
                $table->dropColumn([
                    'stripe_customer_id',
                    'plan_id',
                    'plan_status',
                    'active_until',
                    'verify_token',
                    'verified',
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     * Note: Reversing this is not fully supported since data is lost.
     */
    public function down(): void
    {
        // No down migration provided for SaaS removal
    }
};
