<?php

namespace Database\Seeders;

use App\Models\PlaceCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the 20 Geoapify place categories used by the nearby-POI pipeline.
 *
 * The `slug` values are the raw Geoapify Places-API category keys and MUST NOT
 * be renamed — they are the match keys used for API filtering (exact or
 * child-prefix). The localized labels and icons are admin-editable defaults.
 *
 * Idempotent: firstOrCreate by slug — never overwrites admin edits.
 */
class PlaceCategorySeeder extends Seeder
{
    /**
     * @return array<int, array{slug: string, name_id: string, name_en: string, icon: string, color: string}>
     */
    public static function defaults(): array
    {
        return [
            ['slug' => 'activity.events_venue',        'name_id' => 'Venue Acara',          'name_en' => 'Events Venue',       'icon' => 'fa-solid fa-calendar-star',  'color' => '#f59e0b'],
            ['slug' => 'airport',                      'name_id' => 'Bandara',              'name_en' => 'Airport',            'icon' => 'fa-solid fa-plane',           'color' => '#0ea5e9'],
            ['slug' => 'commercial.gift_and_souvenir', 'name_id' => 'Toko Souvenir',        'name_en' => 'Gift & Souvenir',    'icon' => 'fa-solid fa-gift',            'color' => '#ec4899'],
            ['slug' => 'catering.bar',                 'name_id' => 'Bar',                  'name_en' => 'Bar',                'icon' => 'fa-solid fa-martini-glass',   'color' => '#a855f7'],
            ['slug' => 'catering.cafe',                'name_id' => 'Kafe',                 'name_en' => 'Cafe',               'icon' => 'fa-solid fa-mug-hot',         'color' => '#b45309'],
            ['slug' => 'education.college',            'name_id' => 'Sekolah Tinggi',       'name_en' => 'College',            'icon' => 'fa-solid fa-graduation-cap',  'color' => '#6366f1'],
            ['slug' => 'education.university',         'name_id' => 'Universitas',          'name_en' => 'University',         'icon' => 'fa-solid fa-building-columns', 'color' => '#4f46e5'],
            ['slug' => 'childcare',                    'name_id' => 'Penitipan Anak',       'name_en' => 'Childcare',          'icon' => 'fa-solid fa-baby-carriage',   'color' => '#22c55e'],
            ['slug' => 'entertainment.theme_park',     'name_id' => 'Taman Hiburan',        'name_en' => 'Theme Park',         'icon' => 'fa-solid fa-ferris-wheel',    'color' => '#e11d48'],
            ['slug' => 'entertainment.water_park',     'name_id' => 'Water Park',           'name_en' => 'Water Park',         'icon' => 'fa-solid fa-water-ladder',    'color' => '#06b6d4'],
            ['slug' => 'healthcare.hospital',          'name_id' => 'Rumah Sakit',          'name_en' => 'Hospital',           'icon' => 'fa-solid fa-hospital',        'color' => '#ef4444'],
            ['slug' => 'national_park',                'name_id' => 'Taman Nasional',       'name_en' => 'National Park',      'icon' => 'fa-solid fa-tree',            'color' => '#16a34a'],
            ['slug' => 'office.government',            'name_id' => 'Kantor Pemerintahan',  'name_en' => 'Government Office',  'icon' => 'fa-solid fa-landmark',        'color' => '#64748b'],
            ['slug' => 'rental.car',                   'name_id' => 'Rental Mobil',         'name_en' => 'Car Rental',         'icon' => 'fa-solid fa-car',             'color' => '#0284c7'],
            ['slug' => 'emergency.ambulance_station',  'name_id' => 'Stasiun Ambulans',     'name_en' => 'Ambulance Station',  'icon' => 'fa-solid fa-truck-medical',   'color' => '#dc2626'],
            ['slug' => 'service.police',               'name_id' => 'Kantor Polisi',        'name_en' => 'Police',             'icon' => 'fa-solid fa-shield-halved',   'color' => '#1d4ed8'],
            ['slug' => 'tourism',                      'name_id' => 'Wisata',               'name_en' => 'Tourism',            'icon' => 'fa-solid fa-umbrella-beach',  'color' => '#f97316'],
            ['slug' => 'religion',                     'name_id' => 'Tempat Ibadah',        'name_en' => 'Place of Worship',   'icon' => 'fa-solid fa-place-of-worship', 'color' => '#14b8a6'],
            ['slug' => 'sport',                        'name_id' => 'Olahraga',             'name_en' => 'Sports',             'icon' => 'fa-solid fa-futbol',          'color' => '#84cc16'],
            ['slug' => 'public_transport',             'name_id' => 'Transportasi Umum',    'name_en' => 'Public Transport',   'icon' => 'fa-solid fa-train-subway',    'color' => '#7c3aed'],
        ];
    }

    public function run(): void
    {
        foreach (self::defaults() as $index => $category) {
            PlaceCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category + ['sort_order' => $index]
            );
        }
    }
}
