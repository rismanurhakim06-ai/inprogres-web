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
        $emailsByTarget = [
            'lppm' => 'user.lppm@example.com',
            'lpm' => 'user.lpm@example.com',
            'ma' => 'user.ma@example.com',
            'trpl' => 'user.trpl@example.com',
            'bk' => 'user.bk@example.com',
        ];

        foreach ($emailsByTarget as $target => $email) {
            $userId = DB::table('users')->where('email', $email)->value('id');

            if ($userId !== null) {
                DB::table('tickets')
                    ->whereNull('user_id')
                    ->where('target', $target)
                    ->update(['user_id' => $userId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ticket ownership is intentionally preserved when rolling back this data migration.
    }
};
