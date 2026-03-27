<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Plus (ID 1) -> 10 Domains
        DB::table('plans')->where('id', 1)->update(['max_domains' => 10]);

        // Pro (ID 2) -> 25 Domains
        DB::table('plans')->where('id', 2)->update(['max_domains' => 25]);

        // Ultimate (ID 3) -> 100 Domains
        DB::table('plans')->where('id', 3)->update(['max_domains' => 100]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->update(['max_domains' => null]);
    }
};
