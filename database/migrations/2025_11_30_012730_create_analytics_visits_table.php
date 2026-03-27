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
        if (!Schema::hasTable('analytics_visits')) {
            Schema::create('analytics_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
                $table->string('visitor_hash')->index();
                $table->text('url');
                $table->string('path');
                $table->text('referrer')->nullable();
                $table->string('referrer_domain')->nullable();
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->string('device')->nullable(); // 'desktop', 'mobile', 'tablet'
                $table->string('country', 2)->nullable();
                $table->timestamp('created_at')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_visits');
    }
};
