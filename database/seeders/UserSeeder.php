<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users programmatically
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'), // Change this password
        ]);

        User::create([
            'name' => 'Service Team Member',
            'email' => 'service@example.com',
            'password' => Hash::make('password123'), // Change this password
        ]);

        // Add more users as needed
        // User::create([
        //     'name' => 'Another User',
        //     'email' => 'user@example.com',
        //     'password' => Hash::make('secure_password'),
        // ]);
    }
}

