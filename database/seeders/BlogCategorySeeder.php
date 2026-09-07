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
                'name' => 'Tips Sewa Apartemen',
                'slug' => 'tips-sewa-apartemen',
                'description' => 'Panduan dan tips praktis untuk menyewa apartemen harian, mingguan, maupun bulanan.',
            ],
            [
                'name' => 'Review Properti',
                'slug' => 'review-properti',
                'description' => 'Ulasan lengkap berbagai properti apartemen di Jabodetabek dan sekitarnya.',
            ],
            [
                'name' => 'Panduan Wisata',
                'slug' => 'panduan-wisata',
                'description' => 'Rekomendasi destinasi wisata dan kuliner di sekitar kawasan apartemen kami.',
            ],
            [
                'name' => 'Informasi Properti',
                'slug' => 'informasi-properti',
                'description' => 'Berita dan informasi terbaru seputar dunia properti dan apartemen Indonesia.',
            ],
            [
                'name' => 'Gaya Hidup Urban',
                'slug' => 'gaya-hidup-urban',
                'description' => 'Inspirasi gaya hidup modern untuk penghuni apartemen dan kawasan perkotaan.',
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $tags = [
            ['name' => 'Apartemen Harian',    'slug' => 'apartemen-harian',    'description' => 'Panduan dan tips sewa apartemen harian di Jabodetabek: pilihan unit, harga, dan prosesnya.'],
            ['name' => 'Sewa Murah',           'slug' => 'sewa-murah',          'description' => 'Tips dan pilihan sewa apartemen hemat, mulai dari paket transit sampai harga mingguan.'],
            ['name' => 'BSD City',             'slug' => 'bsd-city',            'description' => 'Panduan apartemen, tempat menginap, transportasi, dan kuliner di kawasan BSD City.'],
            ['name' => 'Bintaro',              'slug' => 'bintaro',             'description' => 'Panduan menginap dan tips hemat di kawasan Bintaro, Tangerang Selatan.'],
            ['name' => 'Tangerang',            'slug' => 'tangerang',           'description' => 'Pilihan apartemen dan panduan sewa di kawasan Tangerang dan sekitarnya.'],
            ['name' => 'Jakarta',              'slug' => 'jakarta',             'description' => 'Pilihan apartemen sewa harian dan transit di kawasan Jakarta.'],
            ['name' => 'Bekasi',               'slug' => 'bekasi',              'description' => 'Pilihan apartemen dan panduan menginap di kawasan Bekasi.'],
            ['name' => 'Transit',              'slug' => 'transit',             'description' => 'Sewa apartemen transit 3-24 jam: cocok untuk perjalanan bisnis dan transit bandara.'],
            ['name' => 'Keluarga',             'slug' => 'keluarga',            'description' => 'Pilihan apartemen harian yang nyaman untuk staycation bersama keluarga.'],
            ['name' => 'Staycation',           'slug' => 'staycation',          'description' => 'Ide staycation hemat: apartemen dengan fasilitas hotel di Jabodetabek.'],
            ['name' => 'Work From Apartment',  'slug' => 'work-from-apartment', 'description' => 'Apartemen dengan area kerja dan Wi-Fi untuk work from apartment yang produktif.'],
            ['name' => 'PIK 2',                'slug' => 'pik-2',               'description' => 'Panduan apartemen dan tempat menginap di kawasan PIK 2.'],
            ['name' => 'Studio',               'slug' => 'studio',              'description' => 'Pilihan apartemen studio untuk sewa harian sampai bulanan.'],
            ['name' => '1 Bedroom',            'slug' => '1-bedroom',           'description' => 'Pilihan apartemen 1 bedroom untuk menginap jangka pendek maupun panjang.'],
            ['name' => 'Dekat Bandara',        'slug' => 'dekat-bandara',       'description' => 'Apartemen dekat bandara untuk transit atau menginap sebelum penerbangan.'],
        ];

        foreach ($tags as $tag) {
            // Idempotent: jangan menimpa description hasil edit admin.
            $model = Tag::firstOrCreate(['slug' => $tag['slug']], $tag);
            if ($model->description === null && ($tag['description'] ?? '') !== '') {
                $model->update(['description' => $tag['description']]);
            }
        }

        $this->command->info('BlogCategorySeeder: '.count($categories).' kategori & '.count($tags).' tag berhasil di-seed.');
    }
}
