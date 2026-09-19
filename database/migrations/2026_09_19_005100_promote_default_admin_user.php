<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('email', 'test@example.com')
                ->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        // Keep the account usable when rolling back this data correction.
    }
};
