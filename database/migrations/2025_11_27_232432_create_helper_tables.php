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
        if (!Schema::hasTable('discount_rules')) {
            Schema::create('discount_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trigger_module_id')->constrained('modules')->onDelete('cascade');
                $table->foreignId('target_module_id')->constrained('modules')->onDelete('cascade');
                $table->integer('discount_percent')->default(0);
            });
        }

        if (!Schema::hasTable('domain_add_limits')) {
            Schema::create('domain_add_limits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Note: Legacy didn't cascade, but it makes sense
                $table->timestamp('last_domain_add')->useCurrent();
                $table->integer('domains_added_today')->default(0);
                $table->date('last_reset_date')->nullable();
            });
        }

        if (!Schema::hasTable('domain_deletion_log')) {
            Schema::create('domain_deletion_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Note: Legacy didn't cascade
                $table->string('domain_name');
                $table->timestamp('deleted_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('domain_notes')) {
            Schema::create('domain_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained()->onDelete('cascade'); // Legacy didn't cascade
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Legacy didn't cascade
                $table->text('note_content');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('manual_check_limits')) {
            Schema::create('manual_check_limits', function (Blueprint $table) {
                $table->foreignId('user_id')->primary()->constrained()->onDelete('cascade');
                $table->integer('manual_checks_today')->default(0);
                $table->date('last_manual_check_date')->nullable();
            });
        }

        if (!Schema::hasTable('stripe_events')) {
            Schema::create('stripe_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id')->unique();
                $table->string('event_type');
                $table->longText('payload')->nullable();
                $table->string('status', 50)->default('received');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // Removed constrained() to prevent errno 150
                $table->string('type'); // email, sms, etc.
                $table->text('message');
                $table->boolean('read')->default(false);
                $table->timestamps();
                
                // Optional: Add index for performance without enforcing FK constraint
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('manual_check_limits');
        Schema::dropIfExists('domain_notes');
        Schema::dropIfExists('domain_deletion_log');
        Schema::dropIfExists('domain_add_limits');
        Schema::dropIfExists('discount_rules');
    }
};
