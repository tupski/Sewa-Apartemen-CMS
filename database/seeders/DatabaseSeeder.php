<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for testing
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@sewaapartemen.com',
            'email_verified_at' => now(),
        ]);

        // Run role seeder, setting seeder, and amenity master data
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            AmenitySeeder::class,
            BlogCategorySeeder::class,
            PropertySeeder::class,
            PostSeeder::class,
        ]);
    }
}
