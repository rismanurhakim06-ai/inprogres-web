<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'owner',
                'role' => 'owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'supervisor@example.com'],
            [
                'name' => 'Supervisor Inprogres',
                'role' => 'supervisor',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User Inprogres',
                'role' => 'user',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        foreach ([
            'lpm' => ['user.lpm@example.com', 'User Web LPM'],
            'lppm' => ['user.lppm@example.com', 'User Web LPPM'],
            'ma' => ['user.ma@example.com', 'User Web MA'],
            'trpl' => ['user.trpl@example.com', 'User Web TRPL'],
            'bk' => ['user.bk@example.com', 'User Web BK'],
        ] as $target => [$email, $name]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'role' => 'user',
                    'target' => $target,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
