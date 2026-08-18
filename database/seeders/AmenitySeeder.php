<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    /**
     * Master data fasilitas apartemen standar (bahasa Indonesia).
     * Idempotent: firstOrCreate by slug — tidak menduplikat, tidak menimpa data user.
     */
    public function run(): void
    {
        $amenities = [
            // Kategori unit
            ['name' => 'AC', 'icon' => '❄️', 'category' => 'unit'],
            ['name' => 'WiFi', 'icon' => '📶', 'category' => 'unit'],
            ['name' => 'Dapur', 'icon' => '🍳', 'category' => 'unit'],
            ['name' => 'Kulkas', 'icon' => '🧊', 'category' => 'unit'],
            ['name' => 'Mesin Cuci', 'icon' => '🧺', 'category' => 'unit'],
            ['name' => 'TV Kabel / Streaming', 'icon' => '📺', 'category' => 'unit'],
            ['name' => 'Balkon', 'icon' => '🌤️', 'category' => 'unit'],
            ['name' => 'Interior Lengkap', 'icon' => '🛋️', 'category' => 'unit'],

            // Kategori property
            ['name' => 'Parkir Gratis', 'icon' => '🅿️', 'category' => 'property'],
            ['name' => 'Kolam Renang', 'icon' => '🏊', 'category' => 'property'],
            ['name' => 'Gym', 'icon' => '🏋️', 'category' => 'property'],
            ['name' => 'Keamanan 24 Jam', 'icon' => '🛡️', 'category' => 'property'],
            ['name' => 'CCTV', 'icon' => '📹', 'category' => 'property'],
            ['name' => 'Layanan Kebersihan', 'icon' => '🧹', 'category' => 'property'],
            ['name' => 'Lift', 'icon' => '🛗', 'category' => 'property'],
            ['name' => 'Akses Disabilitas', 'icon' => '♿', 'category' => 'property'],
            ['name' => 'Taman / Area Bermain', 'icon' => '🌳', 'category' => 'property'],
            ['name' => 'Kantin / Kafe', 'icon' => '☕', 'category' => 'property'],
            ['name' => 'Laundry', 'icon' => '🧼', 'category' => 'property'],
            ['name' => 'Mushola', 'icon' => '🕌', 'category' => 'property'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(
                ['slug' => Str::slug($amenity['name'])],
                [
                    'name' => $amenity['name'],
                    'icon' => $amenity['icon'],
                    'category' => $amenity['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
