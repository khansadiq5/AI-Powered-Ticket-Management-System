<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Super Admin
        User::updateOrCreate(
            ['email' => 'admin123@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin@123'),
                'role' => 'admin',
            ]
        );

        // Seed Agent
        User::updateOrCreate(
            ['email' => 'agent123@gmail.com'],
            [
                'name' => 'Agent',
                'password' => Hash::make('agent@123'),
                'role' => 'agent',
            ]
        );

        // Seed AI Agent
        User::updateOrCreate(
            ['email' => 'ai.assistant@helpdesk.com'],
            [
                'name' => 'AI',
                'password' => Hash::make('ai-assistant-secret-password-123'),
                'role' => 'agent',
            ]
        );
    }
}
