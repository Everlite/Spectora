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
        if (!Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('url');
                $table->string('last_status')->nullable();
                $table->dateTime('last_checked')->nullable();
                $table->string('last_ip', 45)->nullable();
                $table->string('safety_status', 50)->nullable();
                $table->integer('ssl_days_left')->nullable();
                $table->decimal('last_response_time', 6, 3)->nullable();
                $table->string('keyword_must_contain')->nullable();
                $table->string('keyword_must_not_contain')->nullable();
                $table->boolean('notify_sent')->default(false);
                $table->timestamps(); // created_at, updated_at (Laravel standard, though legacy didn't have created_at)
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
