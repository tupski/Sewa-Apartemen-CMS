<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Seed kategori dan tag blog apartemen.
     * Idempotent: firstOrCreate by slug.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Tips Sewa Apartemen',
                'slug'        => 'tips-sewa-apartemen',
                'description' => 'Panduan dan tips praktis untuk menyewa apartemen harian, mingguan, maupun bulanan.',
            ],
            [
                'name'        => 'Review Properti',
                'slug'        => 'review-properti',
                'description' => 'Ulasan lengkap berbagai properti apartemen di Jabodetabek dan sekitarnya.',
            ],
            [
                'name'        => 'Panduan Wisata',
                'slug'        => 'panduan-wisata',
                'description' => 'Rekomendasi destinasi wisata dan kuliner di sekitar kawasan apartemen kami.',
            ],
            [
                'name'        => 'Informasi Properti',
                'slug'        => 'informasi-properti',
                'description' => 'Berita dan informasi terbaru seputar dunia properti dan apartemen Indonesia.',
            ],
            [
                'name'        => 'Gaya Hidup Urban',
                'slug'        => 'gaya-hidup-urban',
                'description' => 'Inspirasi gaya hidup modern untuk penghuni apartemen dan kawasan perkotaan.',
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $tags = [
            ['name' => 'Apartemen Harian',    'slug' => 'apartemen-harian'],
            ['name' => 'Sewa Murah',           'slug' => 'sewa-murah'],
            ['name' => 'BSD City',             'slug' => 'bsd-city'],
            ['name' => 'Bintaro',              'slug' => 'bintaro'],
            ['name' => 'Tangerang',            'slug' => 'tangerang'],
            ['name' => 'Jakarta',              'slug' => 'jakarta'],
            ['name' => 'Bekasi',               'slug' => 'bekasi'],
            ['name' => 'Transit',              'slug' => 'transit'],
            ['name' => 'Keluarga',             'slug' => 'keluarga'],
            ['name' => 'Staycation',           'slug' => 'staycation'],
            ['name' => 'Work From Apartment',  'slug' => 'work-from-apartment'],
            ['name' => 'PIK 2',                'slug' => 'pik-2'],
            ['name' => 'Studio',               'slug' => 'studio'],
            ['name' => '1 Bedroom',            'slug' => '1-bedroom'],
            ['name' => 'Dekat Bandara',        'slug' => 'dekat-bandara'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }

        $this->command->info('BlogCategorySeeder: ' . count($categories) . ' kategori & ' . count($tags) . ' tag berhasil di-seed.');
    }
}
