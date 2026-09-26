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
        DB::table('users')
            ->where('email', 'owner@example.com')
            ->where('role', 'owner')
            ->update(['role' => 'superadmin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('email', 'owner@example.com')
            ->where('role', 'superadmin')
            ->update(['role' => 'owner']);
    }
};
