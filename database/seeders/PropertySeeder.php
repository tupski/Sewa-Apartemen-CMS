<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends Seeder
{
    /**
     * Seed 9 apartemen lengkap dengan data real, harga, dan foto.
     * Idempotent: updateOrCreate by slug.
     * Foto di-rotate dari media id 1–8 (milik Skyhouse BSD) agar semua tampil.
     */
    public function run(): void
    {
        // Ambil semua media yang tersedia untuk di-rotate
        $allMediaIds = Media::pluck('id')->toArray();
        if (empty($allMediaIds)) {
            $this->command->warn('Tidak ada media di database. PropertySeeder melewati foto.');
            $allMediaIds = [1]; // fallback agar tidak error
        }

        $properties = $this->propertyData();

        foreach ($properties as $order => $data) {
            $amenityIds = $data['amenity_ids'] ?? [];
            unset($data['amenity_ids']);

            $property = Property::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['order' => $order])
            );

            // Set featured_image ke media pertama jika belum ada
            if (is_null($property->featured_image_id) && !empty($allMediaIds)) {
                $property->update(['featured_image_id' => $allMediaIds[0]]);
            }

            // Buat property_photos — rotate media supaya semua foto tampil
            // Hapus dulu yang lama supaya idempotent
            PropertyPhoto::where('property_id', $property->id)->delete();

            $mediaCount = count($allMediaIds);
            $categories = ['Lobby', 'Bedroom', 'Bedroom', 'Bedroom', 'Swimming Pool', 'View', 'View', 'Bedroom'];
            foreach ($allMediaIds as $idx => $mediaId) {
                PropertyPhoto::create([
                    'property_id' => $property->id,
                    'media_id'    => $mediaId,
                    'category'    => $categories[$idx % count($categories)],
                    'sort_order'  => $idx,
                ]);
            }

            // Sync amenities jika tabel pivot ada
            if (DB::getSchemaBuilder()->hasTable('property_amenity') && !empty($amenityIds)) {
                DB::table('property_amenity')
                    ->where('property_id', $property->id)
                    ->delete();
                foreach ($amenityIds as $amenityId) {
                    DB::table('property_amenity')->insert([
                        'property_id' => $property->id,
                        'amenity_id'  => $amenityId,
                    ]);
                }
            }
        }

        $this->command->info('PropertySeeder: ' . count($properties) . ' properti berhasil di-seed.');
    }

    private function propertyData(): array
    {
        // Struktur harga mengikuti Skyhouse BSD (studio sebagai tipe dasar)
        // Properti dengan 1BR/2BR menggunakan harga yang lebih tinggi secara proporsional
        // weekend_days: 0=Minggu, 5=Jumat, 6=Sabtu

        return [
            // ─────────────────────────────────────────
            // 1. Skyhouse BSD (sudah ada — update data lengkap)
            // ─────────────────────────────────────────
            [
                'name'               => 'Skyhouse BSD',
                'slug'               => 'skyhouse-bsd',
                'description'        => 'Skyhouse BSD adalah apartemen modern di kawasan BSD City, Tangerang Selatan. Dikelola oleh Summarecon, apartemen ini menawarkan hunian nyaman dengan fasilitas lengkap dan akses mudah ke berbagai pusat perbelanjaan, sekolah internasional, dan rumah sakit. Cocok untuk sewa harian, transit, hingga bulanan.',
                'address'            => 'Jl. Pahlawan Seribu, BSD City, Lengkong Gudang',
                'city'               => 'Tangerang Selatan',
                'province'           => 'Banten',
                'postal_code'        => '15321',
                'latitude'           => -6.30194,
                'longitude'          => 106.65361,
                'status'             => 'published',
                'is_featured'        => true,
                'unit_types'         => ['studio', '1br'],
                'weekend_days'       => [0, 5, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'Playground', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 350000, 'night_we' => 400000,
                        't3_wd' => 150000,   't3_we' => 200000,
                        't6_wd' => 200000,   't6_we' => 250000,
                        't9_wd' => 250000,   't9_we' => 300000,
                        't12_wd' => 300000,  't12_we' => 350000,
                        't24_wd' => 450000,  't24_we' => 500000,
                    ],
                    '1br' => [
                        'night_wd' => 450000, 'night_we' => 500000,
                        't3_wd' => 200000,   't3_we' => 250000,
                        't6_wd' => 280000,   't6_we' => 330000,
                        't9_wd' => 350000,   't9_we' => 400000,
                        't12_wd' => 400000,  't12_we' => 450000,
                        't24_wd' => 600000,  't24_we' => 650000,
                    ],
                ],
                'checkin_time'       => '14:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Tamu bertemu staff di lobby untuk serah terima kunci',
                'required_documents' => ['KTP / Identitas resmi', 'Kartu Keluarga (untuk tamu pasangan)'],
                'meta_title'         => 'Sewa Apartemen Skyhouse BSD Harian & Bulanan — Mulai Rp 150.000',
                'meta_description'   => 'Sewa apartemen Skyhouse BSD murah, bisa jam-jaman, harian, atau bulanan. Fasilitas lengkap: kolam renang, gym, parkir gratis. Cek harga & booking sekarang!',
                'nearby_places'      => [
                    ['name' => 'AEON Mall BSD City', 'category' => 'Entertainment/Attraction', 'distance_km' => 1.2, 'address' => 'Jl. BSD Raya Utama'],
                    ['name' => 'RS Eka Hospital BSD', 'category' => 'Nearby Places', 'distance_km' => 0.8, 'address' => 'Jl. BSD Raya Utama'],
                    ['name' => 'Stasiun Cisauk (KRL)', 'category' => 'Transportation', 'distance_km' => 2.5, 'address' => 'Cisauk, Tangerang'],
                    ['name' => 'The Breeze BSD City', 'category' => 'Entertainment/Attraction', 'distance_km' => 1.5],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 17, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 2. Treepark BSD
            // ─────────────────────────────────────────
            [
                'name'               => 'Treepark BSD',
                'slug'               => 'treepark-bsd',
                'description'        => 'Treepark BSD adalah apartemen mixed-use yang terintegrasi dengan pusat perbelanjaan di kawasan BSD City. Dengan konsep green living, apartemen ini dikelilingi taman hijau dan fasilitas modern. Lokasi strategis dekat pintu tol BSD dan Stasiun Cisauk, ideal untuk profesional muda dan keluarga kecil.',
                'address'            => 'Jl. Pahlawan Seribu No.1, BSD City',
                'city'               => 'Tangerang Selatan',
                'province'           => 'Banten',
                'postal_code'        => '15322',
                'latitude'           => -6.30528,
                'longitude'          => 106.65083,
                'status'             => 'published',
                'is_featured'        => true,
                'unit_types'         => ['studio', '1br', '2br'],
                'weekend_days'       => [0, 5, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 330000, 'night_we' => 380000,
                        't3_wd' => 140000,   't3_we' => 190000,
                        't6_wd' => 190000,   't6_we' => 240000,
                        't9_wd' => 240000,   't9_we' => 290000,
                        't12_wd' => 290000,  't12_we' => 340000,
                        't24_wd' => 430000,  't24_we' => 480000,
                    ],
                    '1br' => [
                        'night_wd' => 430000, 'night_we' => 480000,
                        't3_wd' => 190000,   't3_we' => 240000,
                        't6_wd' => 260000,   't6_we' => 310000,
                        't9_wd' => 330000,   't9_we' => 380000,
                        't12_wd' => 380000,  't12_we' => 430000,
                        't24_wd' => 580000,  't24_we' => 630000,
                    ],
                    '2br' => [
                        'night_wd' => 600000, 'night_we' => 670000,
                        't3_wd' => 280000,   't3_we' => 350000,
                        't6_wd' => 380000,   't6_we' => 450000,
                        't9_wd' => 480000,   't9_we' => 550000,
                        't12_wd' => 550000,  't12_we' => 620000,
                        't24_wd' => 800000,  't24_we' => 880000,
                    ],
                ],
                'checkin_time'       => '14:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Self check-in menggunakan kode akses yang dikirim via WhatsApp',
                'required_documents' => ['KTP / Identitas resmi'],
                'meta_title'         => 'Sewa Apartemen Treepark BSD Harian — Mulai Rp 140.000',
                'meta_description'   => 'Sewa Treepark BSD murah, studio hingga 2BR. Green living concept, dekat AEON Mall & pintu tol BSD. Booking online mudah!',
                'nearby_places'      => [
                    ['name' => 'AEON Mall BSD City', 'category' => 'Entertainment/Attraction', 'distance_km' => 0.5],
                    ['name' => 'Pintu Tol BSD', 'category' => 'Transportation', 'distance_km' => 1.0],
                    ['name' => 'Stasiun Cisauk (KRL)', 'category' => 'Transportation', 'distance_km' => 3.0],
                    ['name' => 'RS Eka Hospital BSD', 'category' => 'Nearby Places', 'distance_km' => 1.3],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 17, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 3. Emerald Bintaro
            // ─────────────────────────────────────────
            [
                'name'               => 'Emerald Bintaro',
                'slug'               => 'emerald-bintaro',
                'description'        => 'Emerald Bintaro adalah apartemen premium di kawasan Bintaro Jaya, Tangerang Selatan. Mengusung konsep hunian hijau dengan pemandangan kota yang memukau, apartemen ini dilengkapi fasilitas bintang lima termasuk rooftop infinity pool dan sky lounge. Akses mudah ke Stasiun Jurangmangu dan jalan tol JORR.',
                'address'            => 'Jl. Emerald Bintaro, Sektor 9, Bintaro Jaya',
                'city'               => 'Tangerang Selatan',
                'province'           => 'Banten',
                'postal_code'        => '15228',
                'latitude'           => -6.27500,
                'longitude'          => 106.72083,
                'status'             => 'published',
                'is_featured'        => false,
                'unit_types'         => ['studio', '1br', '2br'],
                'weekend_days'       => [0, 5, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 380000, 'night_we' => 430000,
                        't3_wd' => 160000,   't3_we' => 210000,
                        't6_wd' => 220000,   't6_we' => 270000,
                        't9_wd' => 270000,   't9_we' => 320000,
                        't12_wd' => 320000,  't12_we' => 370000,
                        't24_wd' => 480000,  't24_we' => 530000,
                    ],
                    '1br' => [
                        'night_wd' => 480000, 'night_we' => 530000,
                        't3_wd' => 210000,   't3_we' => 260000,
                        't6_wd' => 290000,   't6_we' => 340000,
                        't9_wd' => 370000,   't9_we' => 420000,
                        't12_wd' => 430000,  't12_we' => 480000,
                        't24_wd' => 630000,  't24_we' => 680000,
                    ],
                    '2br' => [
                        'night_wd' => 650000, 'night_we' => 720000,
                        't3_wd' => 300000,   't3_we' => 370000,
                        't6_wd' => 410000,   't6_we' => 480000,
                        't9_wd' => 510000,   't9_we' => 580000,
                        't12_wd' => 600000,  't12_we' => 670000,
                        't24_wd' => 850000,  't24_we' => 930000,
                    ],
                ],
                'checkin_time'       => '15:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Tamu bertemu staff di lobby tower yang ditentukan',
                'required_documents' => ['KTP / Identitas resmi', 'Bukti pembayaran'],
                'meta_title'         => 'Sewa Apartemen Emerald Bintaro Harian — Mulai Rp 160.000',
                'meta_description'   => 'Sewa Emerald Bintaro premium, infinity pool & sky lounge, dekat Stasiun Jurangmangu. Harga terjangkau mulai Rp 160.000.',
                'nearby_places'      => [
                    ['name' => 'Bintaro Xchange Mall', 'category' => 'Entertainment/Attraction', 'distance_km' => 1.5],
                    ['name' => 'Stasiun Jurangmangu (KRL)', 'category' => 'Transportation', 'distance_km' => 1.8],
                    ['name' => 'RS Medika BSD', 'category' => 'Nearby Places', 'distance_km' => 2.0],
                    ['name' => 'Pintu Tol JORR Bintaro', 'category' => 'Transportation', 'distance_km' => 2.5],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 4. Bintaro Icon
            // ─────────────────────────────────────────
            [
                'name'               => 'Bintaro Icon',
                'slug'               => 'bintaro-icon',
                'description'        => 'Bintaro Icon adalah apartemen mixed-use yang terhubung langsung dengan Bintaro Xchange Mall di Sektor 7, Bintaro Jaya. Nikmati kemudahan berbelanja, kuliner, dan hiburan tanpa harus keluar gedung. Hunian ideal untuk kamu yang menginginkan gaya hidup urban modern di kawasan Tangerang Selatan.',
                'address'            => 'Jl. Jombang Raya, Sektor 7, Bintaro Jaya',
                'city'               => 'Tangerang Selatan',
                'province'           => 'Banten',
                'postal_code'        => '15224',
                'latitude'           => -6.26861,
                'longitude'          => 106.72528,
                'status'             => 'published',
                'is_featured'        => false,
                'unit_types'         => ['studio', '1br'],
                'weekend_days'       => [0, 5, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 360000, 'night_we' => 410000,
                        't3_wd' => 155000,   't3_we' => 205000,
                        't6_wd' => 210000,   't6_we' => 260000,
                        't9_wd' => 260000,   't9_we' => 310000,
                        't12_wd' => 310000,  't12_we' => 360000,
                        't24_wd' => 460000,  't24_we' => 510000,
                    ],
                    '1br' => [
                        'night_wd' => 460000, 'night_we' => 510000,
                        't3_wd' => 205000,   't3_we' => 255000,
                        't6_wd' => 275000,   't6_we' => 325000,
                        't9_wd' => 345000,   't9_we' => 395000,
                        't12_wd' => 410000,  't12_we' => 460000,
                        't24_wd' => 610000,  't24_we' => 660000,
                    ],
                ],
                'checkin_time'       => '14:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Self check-in dengan kartu akses yang diberikan saat booking dikonfirmasi',
                'required_documents' => ['KTP / Identitas resmi'],
                'meta_title'         => 'Sewa Apartemen Bintaro Icon Harian — Terhubung Langsung BXC Mall',
                'meta_description'   => 'Sewa Bintaro Icon murah, connected langsung ke Bintaro Xchange Mall. Studio & 1BR, mulai Rp 155.000. Booking mudah online!',
                'nearby_places'      => [
                    ['name' => 'Bintaro Xchange Mall (BXC)', 'category' => 'Entertainment/Attraction', 'distance_km' => 0.1, 'address' => 'Sektor 7 Bintaro'],
                    ['name' => 'Stasiun Sudimara (KRL)', 'category' => 'Transportation', 'distance_km' => 2.0],
                    ['name' => 'RS Pondok Indah Bintaro Jaya', 'category' => 'Nearby Places', 'distance_km' => 3.5],
                    ['name' => 'Pintu Tol Pondok Ranji', 'category' => 'Transportation', 'distance_km' => 1.2],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 15, 17, 18, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 5. Springwood Residence
            // ─────────────────────────────────────────
            [
                'name'               => 'Springwood Residence',
                'slug'               => 'springwood-residence',
                'description'        => 'Springwood Residence adalah apartemen modern di Tangerang yang menawarkan kenyamanan hunian dengan harga terjangkau. Berlokasi strategis dekat Summarecon Mall Serpong dan akses langsung ke Jl. MH Thamrin Tangerang, apartemen ini cocok untuk profesional maupun keluarga yang mencari hunian sewa di kota Tangerang.',
                'address'            => 'Jl. Imam Bonjol No.888, Karawaci',
                'city'               => 'Kota Tangerang',
                'province'           => 'Banten',
                'postal_code'        => '15115',
                'latitude'           => -6.22194,
                'longitude'          => 106.62528,
                'status'             => 'published',
                'is_featured'        => false,
                'unit_types'         => ['studio', '1br'],
                'weekend_days'       => [0, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 300000, 'night_we' => 350000,
                        't3_wd' => 130000,   't3_we' => 180000,
                        't6_wd' => 180000,   't6_we' => 230000,
                        't9_wd' => 220000,   't9_we' => 270000,
                        't12_wd' => 270000,  't12_we' => 320000,
                        't24_wd' => 400000,  't24_we' => 450000,
                    ],
                    '1br' => [
                        'night_wd' => 400000, 'night_we' => 450000,
                        't3_wd' => 180000,   't3_we' => 230000,
                        't6_wd' => 250000,   't6_we' => 300000,
                        't9_wd' => 310000,   't9_we' => 360000,
                        't12_wd' => 360000,  't12_we' => 410000,
                        't24_wd' => 550000,  't24_we' => 600000,
                    ],
                ],
                'checkin_time'       => '14:00',
                'checkout_time'      => '11:00',
                'checkin_method'     => 'Tamu bertemu penjaga di pos security lobby',
                'required_documents' => ['KTP / Identitas resmi'],
                'meta_title'         => 'Sewa Apartemen Springwood Residence Tangerang — Mulai Rp 130.000',
                'meta_description'   => 'Sewa Springwood Residence Tangerang murah, studio & 1BR, dekat Summarecon Mall Serpong. Harga mulai Rp 130.000/transit.',
                'nearby_places'      => [
                    ['name' => 'Summarecon Mall Serpong', 'category' => 'Entertainment/Attraction', 'distance_km' => 4.5],
                    ['name' => 'Stasiun Tangerang (KRL)', 'category' => 'Transportation', 'distance_km' => 3.0],
                    ['name' => 'RS Siloam Tangerang', 'category' => 'Nearby Places', 'distance_km' => 2.0],
                    ['name' => 'Pintu Tol Karawaci', 'category' => 'Transportation', 'distance_km' => 1.5],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 15, 17, 19],
            ],

            // ─────────────────────────────────────────
            // 6. Treepark City
            // ─────────────────────────────────────────
            [
                'name'               => 'Treepark City',
                'slug'               => 'treepark-city',
                'description'        => 'Treepark City adalah komplek apartemen dan townhouse di kawasan Tangerang yang menawarkan kehidupan nyaman dengan lingkungan asri. Dikembangkan oleh Agung Sedayu Group, proyek ini memadukan hunian vertikal modern dengan konsep taman kota. Strategis di Jl. Hasyim Ashari, mudah akses ke Bandara Soekarno-Hatta.',
                'address'            => 'Jl. Hasyim Ashari, Cipondoh',
                'city'               => 'Kota Tangerang',
                'province'           => 'Banten',
                'postal_code'        => '15148',
                'latitude'           => -6.18000,
                'longitude'          => 106.70833,
                'status'             => 'published',
                'is_featured'        => false,
                'unit_types'         => ['studio', '1br', '2br'],
                'weekend_days'       => [0, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'Playground', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 320000, 'night_we' => 370000,
                        't3_wd' => 135000,   't3_we' => 185000,
                        't6_wd' => 185000,   't6_we' => 235000,
                        't9_wd' => 235000,   't9_we' => 285000,
                        't12_wd' => 280000,  't12_we' => 330000,
                        't24_wd' => 420000,  't24_we' => 470000,
                    ],
                    '1br' => [
                        'night_wd' => 420000, 'night_we' => 470000,
                        't3_wd' => 185000,   't3_we' => 235000,
                        't6_wd' => 255000,   't6_we' => 305000,
                        't9_wd' => 320000,   't9_we' => 370000,
                        't12_wd' => 375000,  't12_we' => 425000,
                        't24_wd' => 560000,  't24_we' => 610000,
                    ],
                    '2br' => [
                        'night_wd' => 580000, 'night_we' => 650000,
                        't3_wd' => 265000,   't3_we' => 335000,
                        't6_wd' => 365000,   't6_we' => 435000,
                        't9_wd' => 460000,   't9_we' => 530000,
                        't12_wd' => 530000,  't12_we' => 600000,
                        't24_wd' => 770000,  't24_we' => 850000,
                    ],
                ],
                'checkin_time'       => '14:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Self check-in dengan kode OTP yang dikirimkan H-1 sebelum check-in',
                'required_documents' => ['KTP / Identitas resmi'],
                'meta_title'         => 'Sewa Apartemen Treepark City Tangerang — Dekat Bandara, Mulai Rp 135.000',
                'meta_description'   => 'Sewa Treepark City Tangerang murah, studio sampai 2BR, dekat Bandara Soetta. Harga mulai Rp 135.000/transit. Booking online!',
                'nearby_places'      => [
                    ['name' => 'Bandara Soekarno-Hatta', 'category' => 'Transportation', 'distance_km' => 8.0],
                    ['name' => 'Lippo Mall Puri', 'category' => 'Entertainment/Attraction', 'distance_km' => 5.5],
                    ['name' => 'RS Siloam Karawaci', 'category' => 'Nearby Places', 'distance_km' => 3.5],
                    ['name' => 'Pintu Tol Cikupa', 'category' => 'Transportation', 'distance_km' => 4.0],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 17, 18, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 7. Tokyo Riverside PIK 2
            // ─────────────────────────────────────────
            [
                'name'               => 'Tokyo Riverside PIK 2',
                'slug'               => 'tokyo-riverside-pik2',
                'description'        => 'Tokyo Riverside PIK 2 adalah apartemen bertema Jepang yang memukau di kawasan Pantai Indah Kapuk 2, Tangerang. Dikembangkan oleh Agung Sedayu Group & Salim Group, proyek ini menawarkan pengalaman hunian ala Tokyo dengan arsitektur modern Jepang, taman sakura, dan akses langsung ke waterfront. Ideal untuk transit bandara maupun liburan akhir pekan.',
                'address'            => 'Kawasan PIK 2, Jl. Pantai Indah Kapuk 2',
                'city'               => 'Tangerang',
                'province'           => 'Banten',
                'postal_code'        => '15560',
                'latitude'           => -6.10222,
                'longitude'          => 106.73194,
                'status'             => 'published',
                'is_featured'        => true,
                'unit_types'         => ['studio', '1br', '2br'],
                'weekend_days'       => [0, 5, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 450000, 'night_we' => 520000,
                        't3_wd' => 190000,   't3_we' => 250000,
                        't6_wd' => 260000,   't6_we' => 320000,
                        't9_wd' => 330000,   't9_we' => 390000,
                        't12_wd' => 390000,  't12_we' => 450000,
                        't24_wd' => 580000,  't24_we' => 650000,
                    ],
                    '1br' => [
                        'night_wd' => 580000, 'night_we' => 650000,
                        't3_wd' => 250000,   't3_we' => 320000,
                        't6_wd' => 350000,   't6_we' => 420000,
                        't9_wd' => 440000,   't9_we' => 510000,
                        't12_wd' => 510000,  't12_we' => 580000,
                        't24_wd' => 770000,  't24_we' => 850000,
                    ],
                    '2br' => [
                        'night_wd' => 800000, 'night_we' => 900000,
                        't3_wd' => 350000,   't3_we' => 440000,
                        't6_wd' => 490000,   't6_we' => 580000,
                        't9_wd' => 620000,   't9_we' => 710000,
                        't12_wd' => 720000,  't12_we' => 820000,
                        't24_wd' => 1050000, 't24_we' => 1150000,
                    ],
                ],
                'checkin_time'       => '15:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Tamu bertemu host di lobi Tokyo Riverside untuk serah terima kunci',
                'required_documents' => ['KTP / Identitas resmi', 'Bukti pembayaran digital'],
                'meta_title'         => 'Sewa Apartemen Tokyo Riverside PIK 2 — Theme Jepang, Dekat Bandara',
                'meta_description'   => 'Sewa Tokyo Riverside PIK 2, apartemen bertema Jepang di kawasan PIK 2. Dekat Bandara Soetta, mulai Rp 190.000. Booking online!',
                'nearby_places'      => [
                    ['name' => 'Bandara Soekarno-Hatta', 'category' => 'Transportation', 'distance_km' => 5.0],
                    ['name' => 'PIK Avenue Mall', 'category' => 'Entertainment/Attraction', 'distance_km' => 8.0],
                    ['name' => 'Waterfront PIK 2', 'category' => 'Entertainment/Attraction', 'distance_km' => 0.5],
                    ['name' => 'Pantai Maju PIK 2', 'category' => 'Entertainment/Attraction', 'distance_km' => 1.2],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 8. Grand Pramuka
            // ─────────────────────────────────────────
            [
                'name'               => 'Grand Pramuka',
                'slug'               => 'grand-pramuka',
                'description'        => 'Grand Pramuka City adalah hunian vertikal terjangkau di kawasan Rawasari, Jakarta Pusat. Berlokasi tepat di sisi Jl. Pramuka dengan akses langsung ke Stasiun Pramuka LRT, apartemen ini menjadi pilihan ideal untuk pekerja kantoran di Jakarta yang membutuhkan hunian transit maupun harian dengan konektivitas transportasi umum yang sangat baik.',
                'address'            => 'Jl. Pramuka No.1, Rawasari, Cempaka Putih',
                'city'               => 'Jakarta Pusat',
                'province'           => 'DKI Jakarta',
                'postal_code'        => '10570',
                'latitude'           => -6.19694,
                'longitude'          => 106.86250,
                'status'             => 'published',
                'is_featured'        => false,
                'unit_types'         => ['studio', '1br'],
                'weekend_days'       => [0, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 400000, 'night_we' => 450000,
                        't3_wd' => 170000,   't3_we' => 220000,
                        't6_wd' => 230000,   't6_we' => 280000,
                        't9_wd' => 280000,   't9_we' => 330000,
                        't12_wd' => 330000,  't12_we' => 380000,
                        't24_wd' => 500000,  't24_we' => 550000,
                    ],
                    '1br' => [
                        'night_wd' => 500000, 'night_we' => 560000,
                        't3_wd' => 220000,   't3_we' => 280000,
                        't6_wd' => 305000,   't6_we' => 365000,
                        't9_wd' => 385000,   't9_we' => 445000,
                        't12_wd' => 445000,  't12_we' => 505000,
                        't24_wd' => 660000,  't24_we' => 720000,
                    ],
                ],
                'checkin_time'       => '14:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Tamu bertemu staff di lobby utama tower A/B/C sesuai unit yang dipesan',
                'required_documents' => ['KTP / Identitas resmi'],
                'meta_title'         => 'Sewa Apartemen Grand Pramuka Jakarta — Dekat LRT, Mulai Rp 170.000',
                'meta_description'   => 'Sewa Grand Pramuka City murah di Jakarta Pusat, dekat Stasiun Pramuka LRT. Studio & 1BR mulai Rp 170.000. Cocok untuk transit & kerja.',
                'nearby_places'      => [
                    ['name' => 'Stasiun Pramuka (LRT Jakarta)', 'category' => 'Transportation', 'distance_km' => 0.2],
                    ['name' => 'Stasiun Pramuka (KRL Commuter)', 'category' => 'Transportation', 'distance_km' => 0.3],
                    ['name' => 'RSUD Tarakan Jakarta', 'category' => 'Nearby Places', 'distance_km' => 2.5],
                    ['name' => 'Matraman Trade Center', 'category' => 'Entertainment/Attraction', 'distance_km' => 1.5],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 15, 17, 19, 20],
            ],

            // ─────────────────────────────────────────
            // 9. Grand Kamala Lagoon
            // ─────────────────────────────────────────
            [
                'name'               => 'Grand Kamala Lagoon',
                'slug'               => 'grand-kamala-lagoon',
                'description'        => 'Grand Kamala Lagoon adalah apartemen superblok mewah di Bekasi yang dikembangkan oleh PT PP Properti. Dengan konsep waterfront living di atas lagoon buatan seluas 10 hektar, apartemen ini menawarkan pengalaman tinggal berkelas dengan pemandangan air yang memukau. Dekat pusat bisnis Bekasi dan akses tol Jakarta–Bekasi.',
                'address'            => 'Jl. KH Noer Ali, Pekayon Jaya, Bekasi Selatan',
                'city'               => 'Bekasi',
                'province'           => 'Jawa Barat',
                'postal_code'        => '17148',
                'latitude'           => -6.24944,
                'longitude'          => 106.99972,
                'status'             => 'published',
                'is_featured'        => true,
                'unit_types'         => ['studio', '1br', '2br'],
                'weekend_days'       => [0, 5, 6],
                'photo_categories'   => ['Lobby', 'Lift', 'Bedroom', 'Toilet', 'Swimming Pool', 'View'],
                'prices'             => [
                    'studio' => [
                        'night_wd' => 420000, 'night_we' => 480000,
                        't3_wd' => 175000,   't3_we' => 230000,
                        't6_wd' => 240000,   't6_we' => 295000,
                        't9_wd' => 300000,   't9_we' => 355000,
                        't12_wd' => 355000,  't12_we' => 410000,
                        't24_wd' => 530000,  't24_we' => 590000,
                    ],
                    '1br' => [
                        'night_wd' => 530000, 'night_we' => 590000,
                        't3_wd' => 230000,   't3_we' => 295000,
                        't6_wd' => 315000,   't6_we' => 380000,
                        't9_wd' => 400000,   't9_we' => 465000,
                        't12_wd' => 460000,  't12_we' => 530000,
                        't24_wd' => 700000,  't24_we' => 770000,
                    ],
                    '2br' => [
                        'night_wd' => 730000, 'night_we' => 820000,
                        't3_wd' => 330000,   't3_we' => 410000,
                        't6_wd' => 450000,   't6_we' => 530000,
                        't9_wd' => 570000,   't9_we' => 650000,
                        't12_wd' => 660000,  't12_we' => 750000,
                        't24_wd' => 960000,  't24_we' => 1060000,
                    ],
                ],
                'checkin_time'       => '15:00',
                'checkout_time'      => '12:00',
                'checkin_method'     => 'Self check-in dengan kartu akses elektronik yang dikirim via email H-1',
                'required_documents' => ['KTP / Identitas resmi', 'Bukti pembayaran'],
                'meta_title'         => 'Sewa Apartemen Grand Kamala Lagoon Bekasi — Waterfront, Mulai Rp 175.000',
                'meta_description'   => 'Sewa Grand Kamala Lagoon Bekasi, waterfront living mewah di atas lagoon 10 ha. Studio–2BR mulai Rp 175.000. Booking online mudah!',
                'nearby_places'      => [
                    ['name' => 'Bekasi Cyber Park', 'category' => 'Entertainment/Attraction', 'distance_km' => 1.5],
                    ['name' => 'Grand Metropolitan Mall', 'category' => 'Entertainment/Attraction', 'distance_km' => 2.0],
                    ['name' => 'Stasiun Bekasi (KRL)', 'category' => 'Transportation', 'distance_km' => 3.5],
                    ['name' => 'RS Mitra Keluarga Bekasi', 'category' => 'Nearby Places', 'distance_km' => 2.5],
                    ['name' => 'Pintu Tol Bekasi Barat', 'category' => 'Transportation', 'distance_km' => 4.0],
                ],
                'amenity_ids'        => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
            ],
        ];
    }
}
