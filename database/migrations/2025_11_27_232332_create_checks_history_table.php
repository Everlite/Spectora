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
        if (!Schema::hasTable('checks_history')) {
            Schema::create('checks_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
                $table->timestamp('checked_at')->useCurrent();
                $table->integer('status_code')->nullable();
                $table->decimal('response_time', 6, 3)->nullable(); // in seconds
                $table->integer('ssl_days_left')->nullable();
                $table->string('safety_status', 50)->nullable();
                $table->integer('pagespeed_score')->nullable();
                $table->integer('pagespeed_score_desktop')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks_history');
    }
};
