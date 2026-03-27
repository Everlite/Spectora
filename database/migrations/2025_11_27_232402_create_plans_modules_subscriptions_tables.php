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
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->enum('type', ['bundle', 'addon'])->default('bundle');
                $table->integer('discount_percent')->default(0);
                $table->text('description')->nullable();
                $table->string('stripe_price_id', 100)->nullable();
                $table->decimal('price_monthly', 10, 2)->nullable();
                $table->decimal('price_yearly', 10, 2)->nullable();
                $table->integer('max_domains')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('features')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->text('description')->nullable();
                $table->decimal('base_price', 10, 2)->nullable();
            });
        }

        if (!Schema::hasTable('plan_modules')) {
            Schema::create('plan_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained()->onDelete('cascade');
                $table->foreignId('module_id')->constrained()->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('plan_prices')) {
            Schema::create('plan_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained()->onDelete('cascade');
                $table->string('stripe_price_id')->unique();
                $table->enum('billing_cycle', ['monthly', 'yearly']);
                $table->decimal('price', 10, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('plan_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('module_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('status', ['active', 'inactive', 'expired'])->default('inactive');
                $table->dateTime('start_date')->useCurrent();
                $table->dateTime('end_date')->nullable();
                $table->decimal('price_paid', 10, 2)->default(0.00);
                $table->string('stripe_subscription_id', 100)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('plan_modules');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('plans');
    }
};
