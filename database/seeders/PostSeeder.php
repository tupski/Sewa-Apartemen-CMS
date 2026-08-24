<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed 18 blog post (cukup untuk 2 halaman dengan 9 post per halaman).
     * Idempotent: updateOrCreate by slug.
     * featured_image menggunakan path media yang sudah ada di storage.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->warn('PostSeeder: tidak ada user. Jalankan DatabaseSeeder dulu.');
            return;
        }

        // Pastikan kategori & tag sudah ada
        $this->call(BlogCategorySeeder::class);

        $catTips     = Category::where('slug', 'tips-sewa-apartemen')->first();
        $catReview   = Category::where('slug', 'review-properti')->first();
        $catWisata   = Category::where('slug', 'panduan-wisata')->first();
        $catInfo     = Category::where('slug', 'informasi-properti')->first();
        $catUrban    = Category::where('slug', 'gaya-hidup-urban')->first();

        // Foto yang tersedia di storage (media id 1 = lobby, 2-8 = bedroom)
        $imgLobby   = 'properties/1/lobby/skyhouse-bsd-leonie-tower-main-lobby-1786987739-qdkKEiNH.jpeg';
        $imgBed1    = 'properties/1/bedroom/pi7-tool-bed-1-1786987740-SQLRWw36.jpeg';
        $imgBed2    = 'properties/1/bedroom/pi7-tool-bed-2-1786987740-sWAFJMF8.jpeg';
        $imgBed3    = 'properties/1/bedroom/pi7-tool-bed-3-1786987740-LJ8Quczo.jpeg';
        $imgBed4    = 'properties/1/bedroom/pi7-tool-bed-4-1786987740-h36mrZ7Y.jpeg';
        $imgBed5    = 'properties/1/bedroom/pi7-tool-bed-5-1786987740-hFj8tUrY.jpeg';
        $imgBed6    = 'properties/1/bedroom/pi7-tool-bed-7-1786987740-AEVOCZP7.jpeg';
        $imgBed7    = 'properties/1/bedroom/pi7-tool-bed-8-1786987740-QQH5uN7G.jpeg';

        $posts = [
            // ── POST 1 ──────────────────────────────────────
            [
                'title'         => 'Tips Memilih Apartemen Sewa Harian yang Tepat di Jabodetabek',
                'slug'          => 'tips-memilih-apartemen-sewa-harian-jabodetabek',
                'category_slug' => 'tips-sewa-apartemen',
                'tag_slugs'     => ['apartemen-harian', 'sewa-murah', 'transit'],
                'featured_image'=> $imgLobby,
                'excerpt'       => 'Menyewa apartemen harian kini makin mudah, tapi pilihan yang salah bisa bikin liburan atau perjalanan bisnis jadi kurang nyaman. Ini dia tips jitu memilih apartemen harian terbaik.',
                'content'       => '<h2>Kenapa Apartemen Harian Makin Populer?</h2><p>Tren menginap di apartemen harian terus meningkat, terutama di kawasan Jabodetabek. Dibanding hotel, apartemen harian menawarkan ruang yang lebih luas, dapur lengkap, dan suasana yang lebih privat — dengan harga yang jauh lebih terjangkau.</p><h2>1. Tentukan Kebutuhan Durasi</h2><p>Sebelum booking, pastikan kamu tahu berapa lama akan menginap. Sewa transit (3–12 jam) cocok untuk perjalanan bisnis singkat atau transit bandara. Sewa harian (overnight) ideal untuk liburan 1–3 malam, sementara sewa mingguan atau bulanan lebih hemat untuk kebutuhan jangka panjang.</p><h2>2. Perhatikan Lokasi dan Aksesibilitas</h2><p>Pilih apartemen yang dekat dengan kebutuhanmu: dekat kantor, stasiun KRL, atau pusat perbelanjaan. Apartemen seperti Skyhouse BSD dekat AEON Mall dan tol BSD, sementara Grand Pramuka Jakarta sangat strategis karena berada tepat di samping Stasiun Pramuka LRT.</p><h2>3. Cek Fasilitas yang Tersedia</h2><p>Fasilitas dasar yang wajib ada: AC, WiFi cepat, air panas, dan kasur berkualitas. Fasilitas tambahan seperti kolam renang, gym, dan parkir gratis adalah nilai plus yang membuat masa menginap semakin menyenangkan.</p><h2>4. Perhatikan Kebijakan Check-in</h2><p>Tanyakan metode check-in sebelum memesan. Ada yang self check-in menggunakan kode akses, ada pula yang perlu bertemu staff. Pastikan jadwal check-in sesuai dengan waktu kedatanganmu untuk menghindari kerepotan.</p><h2>5. Baca Ulasan dan Rating</h2><p>Ulasan dari tamu sebelumnya adalah sumber informasi paling jujur. Perhatikan komentar soal kebersihan, responsivitas host, dan kondisi aktual unit yang berbeda dari foto.</p><h2>Kesimpulan</h2><p>Memilih apartemen harian yang tepat butuh sedikit riset, tapi hasilnya sepadan. Dengan tips di atas, perjalananmu di Jabodetabek akan jauh lebih nyaman dan berkesan.</p>',
                'published_at'  => now()->subDays(30),
            ],
            // ── POST 2 ──────────────────────────────────────
            [
                'title'         => 'Review Skyhouse BSD: Apartemen Modern di Jantung BSD City',
                'slug'          => 'review-skyhouse-bsd-apartemen-modern-bsd-city',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['apartemen-harian', 'bsd-city', 'staycation'],
                'featured_image'=> $imgBed1,
                'excerpt'       => 'Skyhouse BSD menjadi salah satu pilihan terpopuler untuk sewa apartemen harian di BSD City. Kami review lengkap fasilitas, lokasi, dan harganya untuk kamu.',
                'content'       => '<h2>Tentang Skyhouse BSD</h2><p>Skyhouse BSD adalah apartemen bertower tinggi yang dikembangkan oleh Summarecon di kawasan BSD City, Tangerang Selatan. Dengan dua tower utama (Leonie dan Rosewood), apartemen ini menawarkan lebih dari 2.000 unit dengan tipe Studio dan 1 Bedroom.</p><h2>Lokasi Strategis</h2><p>Salah satu keunggulan utama Skyhouse BSD adalah lokasinya yang sangat strategis. Hanya 5 menit berjalan kaki ke AEON Mall BSD City, dan mudah diakses dari pintu tol BSD. Bagi pengguna KRL, Stasiun Cisauk berjarak sekitar 2,5 km atau 7 menit berkendara.</p><h2>Fasilitas Lengkap</h2><p>Skyhouse BSD dilengkapi dengan fasilitas modern termasuk kolam renang outdoor, gym, area BBQ, taman bermain anak, minimarket, dan beberapa pilihan kuliner di ground floor. Keamanan 24 jam dengan sistem CCTV memastikan keamanan penghuninya.</p><h2>Kondisi Unit</h2><p>Unit studio berukuran sekitar 22–28 m² dengan layout yang efisien. Dilengkapi AC, tempat tidur double, lemari pakaian, dapur kecil lengkap dengan kulkas dan microwave, serta kamar mandi dengan shower air panas. WiFi fiber optic tersedia di seluruh unit.</p><h2>Harga Sewa</h2><p>Untuk sewa harian, harga mulai dari Rp 350.000/malam (weekday) untuk tipe Studio. Sewa transit tersedia mulai Rp 150.000 untuk 3 jam. Harga weekend (Jumat–Minggu) sedikit lebih tinggi namun masih sangat terjangkau dibanding hotel berbintang.</p><h2>Verdict</h2><p>Skyhouse BSD layak mendapat rating 4.5/5. Lokasi prima, fasilitas lengkap, dan harga terjangkau menjadikannya pilihan terbaik untuk staycation atau transit di kawasan BSD City.</p>',
                'published_at'  => now()->subDays(27),
            ],
            // ── POST 3 ──────────────────────────────────────
            [
                'title'         => '5 Destinasi Wisata Kuliner Wajib Coba di BSD City',
                'slug'          => '5-destinasi-wisata-kuliner-bsd-city',
                'category_slug' => 'panduan-wisata',
                'tag_slugs'     => ['bsd-city', 'staycation'],
                'featured_image'=> $imgBed2,
                'excerpt'       => 'BSD City bukan cuma soal apartemen dan pusat perbelanjaan. Kawasan ini menyimpan surga kuliner yang sayang dilewatkan saat kamu staycation di sini.',
                'content'       => '<h2>BSD City: Surga Kuliner di Pinggir Kota</h2><p>Siapa sangka BSD City yang dikenal sebagai kota mandiri modern ternyata menyimpan kekayaan kuliner yang luar biasa? Dari kafe aesthetic hingga restoran fine dining, semuanya ada di sini. Berikut 5 destinasi kuliner wajib dicoba saat staycation di BSD.</p><h2>1. The Breeze BSD City</h2><p>The Breeze adalah open-air lifestyle mall yang menjadi ikon BSD City. Di sini kamu akan menemukan puluhan restoran dengan konsep unik, mulai dari pizza artisan, Korean BBQ, hingga dessert café dengan pemandangan danau buatan yang instagramable.</p><h2>2. AEON Mall BSD Food Court</h2><p>Lantai food court AEON Mall BSD menawarkan pilihan kuliner yang sangat beragam dengan harga terjangkau. Tersedia masakan Jepang, Thailand, Indonesia, dan Western dalam satu atap yang nyaman ber-AC.</p><h2>3. Pasar Modern BSD City</h2><p>Untuk pengalaman kuliner yang lebih autentik, kunjungi Pasar Modern BSD. Di sini kamu bisa menemukan berbagai jajanan pasar tradisional, warung makan legendaris, dan toko bahan makanan segar yang buka dari pagi hingga siang.</p><h2>4. Navapark Boulevard</h2><p>Kawasan boulevard di Navapark BSD menawarkan deretan kafe dan restoran dengan nuansa garden yang asri. Cocok untuk makan siang santai atau ngopi sore sembari menikmati suasana hijau khas BSD.</p><h2>5. District 9 Summarecon</h2><p>District 9 adalah food street terbuka yang menjadi favorit anak muda BSD. Tersedia lebih dari 30 tenant kuliner dengan konsep street food modern, dari minuman kekinian hingga fusion food yang unik.</p>',
                'published_at'  => now()->subDays(24),
            ],
            // ── POST 4 ──────────────────────────────────────
            [
                'title'         => 'Mengenal Treepark BSD: Apartemen Green Living di BSD City',
                'slug'          => 'mengenal-treepark-bsd-apartemen-green-living',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['bsd-city', 'apartemen-harian', 'staycation'],
                'featured_image'=> $imgBed3,
                'excerpt'       => 'Treepark BSD hadir dengan konsep green living yang memadukan hunian modern dengan alam. Simak review lengkap fasilitas dan keunggulan apartemen ini.',
                'content'       => '<h2>Treepark BSD: Hunian Hijau di BSD City</h2><p>Treepark BSD adalah apartemen mixed-use yang diintegrasikan dengan mall dan konsep taman hijau. Dikembangkan di kawasan BSD City, proyek ini menawarkan gaya hidup modern yang dekat dengan alam.</p><h2>Konsep Green Living</h2><p>Tidak seperti apartemen biasa, Treepark BSD dirancang dengan konsep green building. Fasad hijau dengan tanaman merambat, taman vertikal di setiap lantai, dan area outdoor yang luas menjadi ciri khasnya. Udara segar dan pemandangan hijau bisa dinikmati langsung dari unit.</p><h2>Fasilitas Unggulan</h2><p>Treepark BSD menyediakan kolam renang olympic size, jogging track di rooftop, gym modern, area BBQ, dan amphitheater outdoor. Terhubung langsung dengan AEON Mall BSD melalui skybridge memudahkan akses belanja dan kuliner.</p><h2>Pilihan Unit</h2><p>Tersedia tipe Studio (22 m²), 1BR (35 m²), dan 2BR (52 m²) yang dirancang efisien dengan pencahayaan alami maksimal. Setiap unit dilengkapi AC, WiFi, dapur lengkap, dan balkon privat.</p><h2>Aksesibilitas</h2><p>Lokasi tepat di jantung BSD City memudahkan akses ke mana-mana. Pintu tol BSD hanya 1 km, dan bus Sinar Jaya tersedia langsung di depan apartemen untuk koneksi ke Stasiun Cisauk.</p>',
                'published_at'  => now()->subDays(21),
            ],
            // ── POST 5 ──────────────────────────────────────
            [
                'title'         => 'Sewa Apartemen untuk Kerja Remote: Pilihan Terbaik di Tangerang Selatan',
                'slug'          => 'sewa-apartemen-kerja-remote-tangerang-selatan',
                'category_slug' => 'tips-sewa-apartemen',
                'tag_slugs'     => ['work-from-apartment', 'tangerang', 'bintaro'],
                'featured_image'=> $imgBed4,
                'excerpt'       => 'Work from anywhere kini bisa dari apartemen sewa harian. Kami rekomendasikan apartemen terbaik di Tangerang Selatan untuk remote worker.',
                'content'       => '<h2>Apartemen Sewa untuk Remote Worker</h2><p>Tren kerja remote membuka peluang baru: bekerja dari apartemen sewa yang nyaman di manapun kamu mau. Tangerang Selatan menjadi pilihan favorit karena biaya hidup lebih terjangkau dibanding Jakarta namun fasilitas tidak kalah lengkap.</p><h2>Kriteria Apartemen Ideal untuk WFA</h2><p>Internet cepat adalah kebutuhan nomor satu. Pastikan apartemen menyediakan WiFi fiber optic dengan kecepatan minimal 50 Mbps. Selain itu, meja kerja yang ergonomis, pencahayaan yang baik, dan suasana tenang sangat penting untuk produktivitas.</p><h2>Rekomendasi 1: Skyhouse BSD</h2><p>Skyhouse BSD menjadi pilihan favorit remote worker karena lokasinya dekat co-working space di AEON Mall dan The Breeze. WiFi fiber optic tersedia di semua unit, dan suasana kawasan BSD yang tenang sangat mendukung produktivitas.</p><h2>Rekomendasi 2: Emerald Bintaro</h2><p>Emerald Bintaro menawarkan pemandangan kota yang memukau — sempurna sebagai backdrop video call profesional. Lokasinya di Sektor 9 Bintaro dekat dengan berbagai kantor perusahaan multinasional.</p><h2>Rekomendasi 3: Bintaro Icon</h2><p>Terhubung langsung dengan BXC Mall yang memiliki area food court buka 24 jam — ideal untuk yang suka kerja malam. Koneksi internet sangat stabil dan akses ke berbagai fasilitas penunjang kerja sangat mudah.</p><h2>Tips Sewa untuk Remote Worker</h2><p>Pertimbangkan sewa mingguan atau bulanan untuk penghematan signifikan. Pastikan tanya soal backup internet (misalnya tersedia koneksi kabel LAN) dan kebijakan tamu yang tidak mengganggu jam kerja.</p>',
                'published_at'  => now()->subDays(18),
            ],
            // ── POST 6 ──────────────────────────────────────
            [
                'title'         => 'Tokyo Riverside PIK 2: Merasakan Atmosfer Jepang di Jakarta',
                'slug'          => 'tokyo-riverside-pik2-atmosfer-jepang-jakarta',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['pik-2', 'staycation', 'dekat-bandara'],
                'featured_image'=> $imgBed5,
                'excerpt'       => 'Apartemen bertema Jepang di PIK 2 ini menawarkan pengalaman menginap yang unik dan berbeda. Review lengkap Tokyo Riverside PIK 2 untuk staycation akhir pekan.',
                'content'       => '<h2>Tokyo Riverside: Sepotong Tokyo di PIK 2</h2><p>Bayangkan menginap di apartemen dengan arsitektur khas Jepang, dikelilingi taman sakura artificial, dengan pemandangan waterfront yang memukau — semua itu bisa kamu rasakan di Tokyo Riverside PIK 2, Tangerang.</p><h2>Konsep dan Desain</h2><p>Developer Agung Sedayu Group & Salim Group menciptakan pengalaman immersive Japanese living di PIK 2. Fasad bangunan terinspirasi dari arsitektur kontemporer Tokyo, lengkap dengan lampu chochin, jembatan koi, dan taman zen miniatur. Instagram-worthy dari segala sudut.</p><h2>Lokasi Strategis untuk Transit Bandara</h2><p>Salah satu keunggulan terbesar Tokyo Riverside adalah jaraknya yang hanya 5 km dari Bandara Soekarno-Hatta. Ideal untuk transit sebelum atau sesudah penerbangan internasional tanpa harus bermacet-macetan ke Jakarta.</p><h2>Fasilitas Premium</h2><p>Infinity pool dengan view waterfront PIK 2, Japanese onsen (hot bath), Sakura Lounge, gym modern, dan private waterfront promenade menjadi fasilitas unggulan. Concierge 24 jam siap membantu kebutuhan tamu.</p><h2>Pilihan Unit</h2><p>Unit Studio, 1BR, hingga 2BR tersedia dengan interior bergaya Japandi — perpaduan Japanese minimalism dan Scandinavian warmth. Setiap unit dilengkapi electronic key dan smart home system.</p><h2>Harga dan Pemesanan</h2><p>Harga sewa harian mulai Rp 450.000 (Studio, weekday). Weekend premium berlaku Jumat hingga Minggu. Tersedia juga paket transit 3–12 jam untuk penumpang transit bandara.</p>',
                'published_at'  => now()->subDays(15),
            ],
            // ── POST 7 ──────────────────────────────────────
            [
                'title'         => 'Grand Kamala Lagoon Bekasi: Waterfront Living yang Memukau',
                'slug'          => 'grand-kamala-lagoon-bekasi-waterfront-living',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['bekasi', 'staycation', '1-bedroom'],
                'featured_image'=> $imgBed6,
                'excerpt'       => 'Grand Kamala Lagoon menghadirkan konsep waterfront living di tengah kota Bekasi. Review lengkap apartemen superblok dengan lagoon buatan 10 hektar ini.',
                'content'       => '<h2>Grand Kamala Lagoon: Bekasi Punya Waterfront</h2><p>Siapa bilang Bekasi tidak punya properti kelas premium? Grand Kamala Lagoon yang dikembangkan PP Properti membuktikan sebaliknya. Dibangun di atas lagoon buatan seluas 10 hektar, apartemen superblok ini menawarkan pengalaman tinggal waterfront yang selama ini identik dengan kawasan Jakarta Utara.</p><h2>Konsep Lagoon Living</h2><p>Keunikan utama Grand Kamala Lagoon adalah lagoon buatan yang mengelilingi seluruh komplek. Penghuni bisa menikmati pemandangan air dari unit, berjalan-jalan di promenade tepi lagoon, atau bersantai di floating deck. Saat matahari terbenam, refleksi cahaya di air lagoon menciptakan pemandangan yang sangat indah.</p><h2>Superblok Lengkap</h2><p>Selain tower hunian, komplek ini dilengkapi dengan Grand Metropolitan Mall, hotel bintang 4, perkantoran, dan fasilitas pendidikan. Semua kebutuhan hidup tersedia dalam satu kawasan terpadu.</p><h2>Konektivitas Transportasi</h2><p>Meski tidak setepat Jakarta, aksesibilitas Grand Kamala Lagoon terus meningkat. Stasiun Bekasi KRL berjarak 3,5 km, dan tol Bekasi Barat memudahkan perjalanan ke Jakarta dalam 30–45 menit di luar jam sibuk.</p><h2>Harga Sewa</h2><p>Studio mulai Rp 420.000/malam (weekday), 1BR mulai Rp 530.000/malam. Tersedia paket transit 3–12 jam untuk kebutuhan singkat. Harga sangat kompetitif mengingat fasilitas premium yang ditawarkan.</p>',
                'published_at'  => now()->subDays(12),
            ],
            // ── POST 8 ──────────────────────────────────────
            [
                'title'         => 'Panduan Lengkap Naik KRL dari BSD ke Jakarta',
                'slug'          => 'panduan-naik-krl-bsd-ke-jakarta',
                'category_slug' => 'panduan-wisata',
                'tag_slugs'     => ['bsd-city', 'tangerang', 'transit'],
                'featured_image'=> $imgLobby,
                'excerpt'       => 'Bepergian dari BSD ke Jakarta dengan KRL Commuter Line kini lebih mudah. Panduan lengkap rute, jadwal, dan tips perjalanan KRL untuk penghuni apartemen BSD.',
                'content'       => '<h2>KRL dari BSD: Mudah dan Murah</h2><p>Bagi penghuni apartemen di kawasan BSD City, KRL Commuter Line adalah pilihan transportasi paling efisien untuk mencapai Jakarta. Dengan tarif mulai Rp 3.000 dan waktu tempuh 45–60 menit ke Stasiun Tanah Abang, KRL menjadi andalan warga BSD yang bekerja di Jakarta.</p><h2>Stasiun Terdekat dari Apartemen BSD</h2><p><strong>Stasiun Cisauk</strong> adalah stasiun KRL terdekat dari kawasan Skyhouse BSD dan Treepark BSD, berjarak sekitar 2,5 km atau 7 menit berkendara. Dari sini, tersedia layanan bus feeder yang menghubungkan stasiun dengan kawasan BSD City.</p><h2>Rute Lengkap</h2><p>Dari Stasiun Cisauk, naik KRL Commuter Line jurusan Jakarta Kota atau Tanah Abang. Perjalanan melewati Stasiun Serpong, Pondok Ranji, Sudimara, hingga Tanah Abang (± 50 menit) atau Jakarta Kota (± 75 menit).</p><h2>Tips Perjalanan KRL</h2><p>1. Gunakan e-money atau Multi Trip Card (MTC) untuk proses yang lebih cepat. 2. Hindari jam sibuk 07.00–09.00 dan 17.00–20.00 jika memungkinkan. 3. Aplikasi KRL Access memudahkan cek jadwal real-time. 4. Pesan ojek online dari stasiun untuk koneksi terakhir ke tujuan.</p><h2>Alternatif: Bus TiJe (Tangerang-Jakarta)</h2><p>Selain KRL, tersedia bus TiJe (Trans Jakarta feeder) dari kawasan BSD yang langsung menuju halte-halte Bus Rapid Transit Jakarta. Tarif Rp 4.000 dengan waktu tempuh bervariasi tergantung kondisi lalu lintas.</p>',
                'published_at'  => now()->subDays(9),
            ],
            // ── POST 9 ──────────────────────────────────────
            [
                'title'         => 'Staycation Budget di Bintaro: Tips Hemat Tanpa Kurang Nyaman',
                'slug'          => 'staycation-budget-bintaro-tips-hemat',
                'category_slug' => 'gaya-hidup-urban',
                'tag_slugs'     => ['bintaro', 'staycation', 'sewa-murah'],
                'featured_image'=> $imgBed7,
                'excerpt'       => 'Staycation tidak harus mahal. Di Bintaro, kamu bisa menikmati menginap berkualitas di apartemen harian dengan budget terbatas. Ini tipsnya!',
                'content'       => '<h2>Staycation Budget: Mitos vs Realita</h2><p>Banyak yang mengira staycation identik dengan pengeluaran besar. Padahal, dengan pilihan yang tepat, kamu bisa menikmati staycation berkualitas di Bintaro dengan budget Rp 300.000–500.000 per malam — lebih hemat dari kebanyakan hotel di Jakarta.</p><h2>Pilih Apartemen, Bukan Hotel</h2><p>Kunci utama staycation hemat adalah memilih apartemen sewa harian dibanding hotel. Apartemen menawarkan dapur lengkap (hemat makan), ruang lebih luas, dan fasilitas serupa hotel dengan harga lebih terjangkau. Emerald Bintaro dan Bintaro Icon adalah dua pilihan top di kawasan ini.</p><h2>Tips Hemat #1: Pesan Weekday</h2><p>Harga sewa weekday (Senin–Kamis) umumnya 10–15% lebih murah dibanding weekend. Kalau jadwalmu fleksibel, manfaatkan kesempatan ini untuk penghematan signifikan.</p><h2>Tips Hemat #2: Sewa Transit untuk Kunjungan Singkat</h2><p>Tidak perlu sewa fullnight jika kamu hanya butuh tempat istirahat 6–9 jam. Sewa transit Emerald Bintaro mulai Rp 220.000 untuk 6 jam — jauh lebih hemat dari tarif hotelnya.</p><h2>Tips Hemat #3: Masak Sendiri</h2><p>Apartemen dengan dapur lengkap memungkinkan kamu hemat pengeluaran makan hingga 50%. Belanja di Bintaro Xchange Mall atau Superindo terdekat, masak sarapan dan makan siang sendiri, dan nikmati restoran hanya untuk makan malam spesial.</p><h2>Rekomendasi Itinerary Staycation Bintaro 1 Malam</h2><p>Check-in pukul 14.00 → Renang & gym → Kuliner di BXC Mall → Istirahat → Sarapan buatan sendiri → Late checkout jam 12.00. Total budget: Rp 450.000 (apartment) + Rp 150.000 (makan) = Rp 600.000 untuk pengalaman premium.</p>',
                'published_at'  => now()->subDays(6),
            ],
            // ── POST 10 ──────────────────────────────────────
            [
                'title'         => 'Grand Pramuka Jakarta: Apartemen Transit Terbaik Dekat LRT',
                'slug'          => 'grand-pramuka-jakarta-apartemen-transit-dekat-lrt',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['jakarta', 'transit', 'apartemen-harian'],
                'featured_image'=> $imgBed1,
                'excerpt'       => 'Grand Pramuka City hadir sebagai solusi hunian transit terbaik di Jakarta Pusat, tepat di samping Stasiun Pramuka LRT dan KRL. Review lengkap untuk commuter Jakarta.',
                'content'       => '<h2>Grand Pramuka: Apartemen Transit di Jantung Jakarta</h2><p>Bagi para commuter dan profesional yang sering bepergian di Jakarta, Grand Pramuka City menawarkan solusi hunian yang tak tertandingi: apartemen terjangkau dengan akses langsung ke dua moda transportasi massal sekaligus — LRT Jakarta dan KRL Commuter Line.</p><h2>Keunggulan Lokasi</h2><p>Stasiun Pramuka LRT hanya 200 meter dari lobby utama, sementara Stasiun Pramuka KRL berjarak 300 meter. Dengan dua stasiun di depan pintu, mobilitas di Jakarta menjadi sangat mudah. Akses ke Sudirman-SCBD dalam 20 menit, Senen dalam 10 menit, dan Manggarai sebagai hub utama hanya 15 menit.</p><h2>Fasilitas</h2><p>Grand Pramuka menyediakan fasilitas lengkap: kolam renang, gym, minimarket 24 jam, dan area parkir yang luas. Terdapat 4 tower dengan total ribuan unit, menjadikannya salah satu apartemen dengan kapasitas terbesar di Jakarta Pusat.</p><h2>Pilihan Unit dan Harga</h2><p>Unit Studio tersedia mulai 22 m² dengan harga sewa harian Rp 400.000/malam (weekday). Paket transit 3 jam tersedia Rp 170.000 — sangat cocok untuk kebutuhan transit singkat. 1BR mulai Rp 500.000/malam.</p><h2>Untuk Siapa?</h2><p>Grand Pramuka ideal untuk: profesional yang bekerja di Jakarta tapi tidak mau bayar sewa bulanan mahal, tamu dari luar kota yang ingin transit nyaman, dan siapapun yang mengutamakan konektivitas transportasi umum.</p>',
                'published_at'  => now()->subDays(5),
            ],
            // ── POST 11 ──────────────────────────────────────
            [
                'title'         => 'Perbandingan Sewa Apartemen vs Hotel: Mana Lebih Hemat?',
                'slug'          => 'perbandingan-sewa-apartemen-vs-hotel-mana-lebih-hemat',
                'category_slug' => 'tips-sewa-apartemen',
                'tag_slugs'     => ['apartemen-harian', 'sewa-murah', 'staycation'],
                'featured_image'=> $imgBed2,
                'excerpt'       => 'Apartemen harian atau hotel? Perdebatan klasik ini punya jawaban yang bergantung pada kebutuhanmu. Kami breakdown perbandingan lengkap biaya, fasilitas, dan kenyamanannya.',
                'content'       => '<h2>Apartemen Harian vs Hotel: Pertanyaan yang Sering Muncul</h2><p>Ketika merencanakan perjalanan atau mencari tempat menginap singkat, pertanyaan ini pasti muncul: pilih apartemen harian atau hotel? Jawabannya tidak sesederhana yang dikira. Mari kita bedah dari berbagai aspek.</p><h2>Perbandingan Harga</h2><p>Untuk budget Rp 400.000–500.000/malam di Jabodetabek, pilihan hotel terbatas pada properti bintang dua dengan fasilitas minim. Dengan budget yang sama di apartemen harian seperti Skyhouse BSD atau Grand Pramuka, kamu bisa mendapat unit yang jauh lebih luas dengan dapur lengkap, kolam renang, dan gym — fasilitas yang di hotel bintang empat bisa mencapai Rp 1.000.000+/malam.</p><h2>Kelebihan Apartemen Harian</h2><p>• Ruang lebih luas (22–52 m² vs kamar hotel 15–20 m²) • Dapur lengkap = hemat makan • Nuansa lebih privat dan homey • Cocok untuk menginap 2 malam atau lebih • Lebih fleksibel untuk keluarga</p><h2>Kelebihan Hotel</h2><p>• Layanan kamar (room service, housekeeping harian) • Lokasi lebih terpusat di kota • Standar konsistensi terjamin • Lebih mudah untuk check-in mendadak</p><h2>Kesimpulan</h2><p>Untuk menginap 1 malam, hotel bisa lebih praktis. Untuk 2 malam atau lebih, apartemen harian hampir selalu lebih hemat dan nyaman. Untuk keluarga atau yang butuh dapur, apartemen jelas juaranya.</p>',
                'published_at'  => now()->subDays(4),
            ],
            // ── POST 12 ──────────────────────────────────────
            [
                'title'         => 'Springwood Residence Tangerang: Hunian Nyaman Dekat Mal Serpong',
                'slug'          => 'springwood-residence-tangerang-hunian-nyaman-dekat-mal-serpong',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['tangerang', 'apartemen-harian', 'keluarga'],
                'featured_image'=> $imgBed3,
                'excerpt'       => 'Springwood Residence hadir sebagai pilihan hunian sewa harian yang terjangkau di Kota Tangerang dengan akses mudah ke berbagai fasilitas publik.',
                'content'       => '<h2>Springwood Residence: Value for Money di Tangerang</h2><p>Di tengah maraknya apartemen premium yang terus bermunculan, Springwood Residence hadir sebagai pilihan yang mengutamakan value for money. Berlokasi di Jl. Imam Bonjol, Karawaci, apartemen ini menjadi favorit bagi mereka yang mencari hunian nyaman dengan harga terjangkau di Kota Tangerang.</p><h2>Lokasi dan Aksesibilitas</h2><p>Springwood Residence terletak strategis di jalur utama Kota Tangerang. Akses ke pintu tol Karawaci hanya 1,5 km, sementara Summarecon Mall Serpong dapat dijangkau dalam 15 menit berkendara. Stasiun Tangerang KRL berjarak 3 km dengan angkutan umum yang tersedia.</p><h2>Fasilitas</h2><p>Dilengkapi kolam renang, gym, area bermain anak, mushola, dan minimarket di ground floor. Keamanan 24 jam dengan akses kartu di setiap lantai memastikan keamanan penghuni. Area parkir basement tersedia luas.</p><h2>Tipe Unit</h2><p>Studio (21 m²) dan 1BR (33 m²) tersedia dalam kondisi fully furnished. Interior bersih dan minimalis dengan pencahayaan alami yang baik. Dilengkapi AC, WiFi, kulkas, dan water heater.</p><h2>Harga Terjangkau</h2><p>Studio mulai Rp 300.000/malam weekday dan Rp 350.000 weekend. Transit 3 jam Rp 130.000 — salah satu yang termurah di kawasan Tangerang. Cocok untuk keluarga kecil yang ingin staycation hemat.</p>',
                'published_at'  => now()->subDays(3),
            ],
            // ── POST 13 ──────────────────────────────────────
            [
                'title'         => 'Treepark City Tangerang: Apartemen Hijau Dekat Bandara Soetta',
                'slug'          => 'treepark-city-tangerang-apartemen-hijau-dekat-bandara',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['tangerang', 'dekat-bandara', 'apartemen-harian'],
                'featured_image'=> $imgBed4,
                'excerpt'       => 'Treepark City menawarkan konsep taman kota di Tangerang dengan akses mudah ke Bandara Soekarno-Hatta. Cocok untuk transit bandara maupun hunian jangka panjang.',
                'content'       => '<h2>Treepark City: Kota Dalam Taman</h2><p>Treepark City adalah pengembangan mixed-use oleh Agung Sedayu Group di kawasan Cipondoh, Tangerang. Dengan konsep "kota dalam taman", proyek ini menghadirkan apartemen, townhouse, ruko, dan fasilitas komersial dalam satu komplek terpadu yang dikelilingi ruang hijau.</p><h2>Keunggulan Lokasi</h2><p>Posisi di Jl. Hasyim Ashari menjadikan Treepark City sangat mudah diakses. Hanya 8 km dari Bandara Soekarno-Hatta melalui tol Cikupa — cocok sebagai tempat transit sebelum/setelah penerbangan. Lippo Mall Puri berjarak 5,5 km untuk kebutuhan belanja dan hiburan.</p><h2>Fasilitas Komplek</h2><p>Kolam renang olimpik, jogging track, taman bermain anak seluas 2 hektar, club house, gym, dan area BBQ tersedia untuk semua penghuni. Komplek juga dilengkapi sekolah, klinik, dan pasar swalayan, menjadikannya kawasan mandiri yang lengkap.</p><h2>Pilihan Unit</h2><p>Studio, 1BR, dan 2BR tersedia dalam kondisi fully furnished. Layout dirancang efisien dengan cross-ventilation yang baik untuk meminimalkan penggunaan AC. Balkon tersedia di sebagian besar unit dengan view taman hijau.</p><h2>Ideal untuk Transit Bandara</h2><p>Dengan jarak 8 km ke Soetta, Treepark City adalah alternatif terbaik dibanding hotel airport yang harganya 2–3x lebih mahal. Studio tersedia mulai Rp 135.000 untuk sewa transit 3 jam.</p>',
                'published_at'  => now()->subDays(2),
            ],
            // ── POST 14 ──────────────────────────────────────
            [
                'title'         => 'Panduan Check-in Apartemen Harian: Apa yang Perlu Disiapkan?',
                'slug'          => 'panduan-check-in-apartemen-harian-persiapan',
                'category_slug' => 'tips-sewa-apartemen',
                'tag_slugs'     => ['apartemen-harian', 'transit', 'keluarga'],
                'featured_image'=> $imgBed5,
                'excerpt'       => 'Proses check-in apartemen harian berbeda dengan hotel. Panduan lengkap ini membantu kamu mempersiapkan dokumen dan mengetahui apa yang akan terjadi saat tiba.',
                'content'       => '<h2>Proses Check-in Apartemen Harian vs Hotel</h2><p>Banyak tamu pertama kali menginap di apartemen harian merasa bingung karena prosesnya berbeda dengan hotel konvensional. Tidak ada resepsionis yang menyambut di lobby, tidak ada bellboy — tapi justru itulah yang membuat pengalaman ini lebih privat dan personal.</p><h2>Dokumen yang Wajib Dibawa</h2><p>1. <strong>KTP/Identitas Resmi</strong> — wajib untuk semua tamu. 2. <strong>Bukti Pemesanan</strong> — screenshot konfirmasi booking. 3. <strong>Bukti Pembayaran</strong> — untuk apartemen yang tidak menggunakan sistem DP. 4. <strong>Kartu Keluarga</strong> — beberapa apartemen meminta untuk tamu berpasangan.</p><h2>Metode Check-in yang Umum</h2><p><strong>Self Check-in:</strong> Kamu menerima kode akses atau PIN via WhatsApp/email H-1. Sangat nyaman untuk kedatangan malam hari atau dini hari tanpa perlu bertemu siapapun.</p><p><strong>Meet and Greet:</strong> Staff atau host menyambut di lobby pada jam yang disepakati. Memungkinkan orientasi fasilitas dan serah terima kunci secara langsung.</p><p><strong>Security Desk:</strong> Beberapa apartemen menggunakan sistem titip kunci di pos security. Tunjukkan identitas, konfirmasi nama pemesan, ambil kunci.</p><h2>Tips agar Check-in Lancar</h2><p>• Konfirmasi metode check-in saat booking • Simpan nomor WhatsApp host/admin • Informasikan jika terlambat atau lebih awal dari jadwal • Foto kondisi unit sebelum digunakan sebagai dokumentasi</p>',
                'published_at'  => now()->subHours(36),
            ],
            // ── POST 15 ──────────────────────────────────────
            [
                'title'         => 'Emerald Bintaro: Hunian Premium dengan Infinity Pool di Bintaro',
                'slug'          => 'emerald-bintaro-hunian-premium-infinity-pool',
                'category_slug' => 'review-properti',
                'tag_slugs'     => ['bintaro', 'staycation', '1-bedroom'],
                'featured_image'=> $imgBed6,
                'excerpt'       => 'Emerald Bintaro menghadirkan nuansa hunian premium di Sektor 9 Bintaro Jaya dengan infinity pool rooftop dan pemandangan kota yang memukau.',
                'content'       => '<h2>Emerald Bintaro: Elegance di Sektor 9</h2><p>Di antara berbagai pilihan apartemen di Bintaro Jaya, Emerald Bintaro menonjol dengan konsep hunian premium yang mengutamakan estetika dan kenyamanan. Berlokasi di Sektor 9, apartemen bertower tinggi ini menawarkan pemandangan kota Tangerang Selatan yang spektakuler dari lantai-lantai atasnya.</p><h2>Infinity Pool Rooftop</h2><p>Daya tarik utama Emerald Bintaro adalah infinity pool di rooftop yang menjadi spot favorit untuk berfoto dan menikmati sunset. View kolam yang seolah menyatu dengan cakrawala kota menciptakan pengalaman visual yang jarang ditemukan di apartemen kelas menengah.</p><h2>Sky Lounge</h2><p>Selain infinity pool, tersedia Sky Lounge di lantai tertinggi yang bisa disewa untuk acara privat. Dengan panorama 360 derajat, venue ini populer untuk gathering profesional dan perayaan ulang tahun premium.</p><h2>Fasilitas Lengkap</h2><p>Gym modern dengan peralatan terkini, jogging track, children playground, mushola, dan minimarket tersedia di dalam komplek. Parkir basement dengan kapasitas besar memudahkan tamu yang membawa kendaraan.</p><h2>Aksesibilitas Bintaro</h2><p>Stasiun Jurangmangu KRL berjarak 1,8 km. Tol JORR Bintaro memudahkan akses ke BSD City (20 menit), Jakarta Selatan (30 menit), dan Tangerang (25 menit).</p>',
                'published_at'  => now()->subHours(24),
            ],
            // ── POST 16 ──────────────────────────────────────
            [
                'title'         => 'Apartemen Harian Ramah Keluarga: Pilihan Terbaik di Jabodetabek',
                'slug'          => 'apartemen-harian-ramah-keluarga-jabodetabek',
                'category_slug' => 'tips-sewa-apartemen',
                'tag_slugs'     => ['keluarga', 'apartemen-harian', 'staycation'],
                'featured_image'=> $imgBed7,
                'excerpt'       => 'Liburan keluarga lebih nyaman di apartemen harian yang luas dibanding kamar hotel sempit. Rekomendasi apartemen ramah keluarga terbaik di Jabodetabek.',
                'content'       => '<h2>Kenapa Keluarga Lebih Cocok di Apartemen?</h2><p>Saat bepergian bersama keluarga — terutama dengan anak kecil — apartemen harian menawarkan pengalaman yang jauh lebih nyaman dibanding hotel. Ruang yang lebih luas, dapur untuk masak MPASI atau sarapan, mesin cuci untuk cucian darurat, dan suasana privat tanpa gangguan tamu lain menjadikan apartemen pilihan ideal.</p><h2>Kriteria Apartemen Ramah Keluarga</h2><p>• <strong>Tipe 2BR atau lebih:</strong> Anak-anak dan orang tua butuh ruang tidur terpisah. • <strong>Dapur lengkap:</strong> Kulkas, microwave, dan peralatan masak dasar. • <strong>Mesin cuci:</strong> Essential untuk perjalanan dengan bayi. • <strong>Taman bermain:</strong> Anak-anak butuh tempat bermain outdoor yang aman. • <strong>Kolam renang:</strong> Aktivitas favorit keluarga yang tidak perlu keluar.</p><h2>Rekomendasi Terbaik</h2><p><strong>Treepark BSD</strong> — 2BR dengan balkon, view taman hijau, dan playground luas. Ideal untuk keluarga yang suka suasana outdoor.</p><p><strong>Treepark City Tangerang</strong> — komplek dengan area taman 2 hektar, klinik di dalam komplek, dan kolam renang besar. Sangat family-friendly.</p><p><strong>Grand Kamala Lagoon Bekasi</strong> — 2BR dengan pemandangan lagoon, area bermain anak, dan mall terintegrasi untuk hiburan lengkap.</p><h2>Tips Booking untuk Keluarga</h2><p>Pesan minimum 2 hari sebelumnya dan konfirmasi ketersediaan tempat tidur ekstra (baby cot). Tanyakan kebijakan anak-anak dan apakah tersedia parkiran basement yang aman.</p>',
                'published_at'  => now()->subHours(12),
            ],
            // ── POST 17 ──────────────────────────────────────
            [
                'title'         => 'Mengenal Sistem Harga Transit Apartemen: 3, 6, 9, hingga 24 Jam',
                'slug'          => 'sistem-harga-transit-apartemen-jam-jaman',
                'category_slug' => 'informasi-properti',
                'tag_slugs'     => ['apartemen-harian', 'transit', 'sewa-murah'],
                'featured_image'=> $imgLobby,
                'excerpt'       => 'Bingung dengan sistem harga transit apartemen yang ada paket 3 jam, 6 jam, 9 jam, hingga 24 jam? Penjelasan lengkap cara kerja dan manfaat tiap paket.',
                'content'       => '<h2>Apa itu Sewa Transit Apartemen?</h2><p>Sewa transit adalah sistem sewa apartemen berdasarkan durasi jam, bukan per malam. Sistem ini sangat populer di apartemen-apartemen harian Indonesia karena fleksibilitasnya yang tinggi — kamu hanya bayar untuk waktu yang benar-benar digunakan.</p><h2>Paket Transit yang Tersedia</h2><p><strong>Transit 3 Jam:</strong> Ideal untuk istirahat singkat, mandi dan bebersih, atau menunggu penerbangan. Harga mulai Rp 130.000–200.000.</p><p><strong>Transit 6 Jam:</strong> Cocok untuk half-day rest, pertemuan singkat, atau kerja dari apartemen setengah hari. Harga mulai Rp 180.000–260.000.</p><p><strong>Transit 9 Jam:</strong> Pilihan populer untuk yang tiba pagi dan perlu istirahat hingga sore, atau perjalanan malam yang butuh tidur siang. Harga mulai Rp 220.000–330.000.</p><p><strong>Transit 12 Jam:</strong> Setara setengah hari penuh. Cocok untuk meeting seharian di luar, lalu istirahat sore hingga malam. Harga mulai Rp 270.000–390.000.</p><p><strong>Transit 24 Jam:</strong> Ini sama dengan sewa 1 malam — tapi dihitung dari jam check-in, bukan berdasarkan tanggal. Lebih fleksibel untuk yang check-in tidak di jam standar.</p><h2>Perbedaan Weekday vs Weekend</h2><p>Hampir semua apartemen menerapkan tarif berbeda untuk akhir pekan (biasanya Jumat, Sabtu, Minggu). Selisihnya sekitar Rp 30.000–70.000 per paket. Rencanakan perjalanan weekday untuk penghematan maksimal.</p>',
                'published_at'  => now()->subHours(6),
            ],
            // ── POST 18 ──────────────────────────────────────
            [
                'title'         => 'Gaya Hidup Urban: Menikmati Tinggal di Apartemen Modern',
                'slug'          => 'gaya-hidup-urban-tinggal-apartemen-modern',
                'category_slug' => 'gaya-hidup-urban',
                'tag_slugs'     => ['apartemen-harian', 'work-from-apartment', 'staycation'],
                'featured_image'=> $imgBed1,
                'excerpt'       => 'Tinggal di apartemen modern bukan hanya soal hunian, tapi gaya hidup. Eksplorasi bagaimana penghuni apartemen modern menjalani hari-hari produktif dan menyenangkan.',
                'content'       => '<h2>Apartemen Modern: Lebih dari Sekadar Tempat Tidur</h2><p>Dekade terakhir menyaksikan pergeseran besar dalam cara orang memaknai hunian. Bagi generasi urban yang dinamis, apartemen modern bukan hanya tempat tidur — ini adalah lifestyle statement. Sebuah hub produktivitas, relaksasi, dan ekspresi diri dalam satu ruang kompak yang efisien.</p><h2>Pagi yang Produktif</h2><p>Bayangkan memulai pagi dengan jogging di track dalam komplek, dilanjut swim di kolam renang yang belum ramai, sarapan buatan sendiri di dapur mini yang lengkap, lalu duduk di meja kerja dengan pemandangan kota yang memotivasi. Itulah kualitas pagi hari di apartemen modern.</p><h2>Bekerja dari Apartemen</h2><p>Work from Apartment (WFA) kini bukan tren musiman — ini pilihan permanen bagi jutaan profesional. WiFi fiber optic yang cepat, suasana tenang di atas lantai tinggi, dan tidak perlu commute menjadikan produktivitas meningkat signifikan.</p><h2>Komunitas yang Terbentuk</h2><p>Salah satu kejutan menyenangkan dari tinggal di apartemen adalah komunitas yang terbentuk. Sesama penghuni yang bertemu di lift, gym, atau kolam renang bisa menjadi koneksi profesional atau bahkan sahabat jangka panjang.</p><h2>Pilihan Tepat untuk Generasi Urban</h2><p>Apakah kamu frequent traveler yang butuh base camp nyaman di beberapa kota, digital nomad yang ingin fleksibilitas tanpa terikat sewa bulanan, atau profesional yang sesekali butuh staycation — apartemen harian modern menjawab semua kebutuhan itu dengan sempurna.</p>',
                'published_at'  => now()->subHours(2),
            ],
        ];
        // Buat/update semua post
        foreach ($posts as $data) {
            $catSlug  = $data['category_slug'];
            $tagSlugs = $data['tag_slugs'] ?? [];
            unset($data['category_slug'], $data['tag_slugs']);

            // Resolve category
            $category = Category::where('slug', $catSlug)->first();

            $post = Post::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'user_id'     => $user->id,
                    'category_id' => $category?->id,
                    'status'      => 'published',
                ])
            );

            // Sync tags
            if (!empty($tagSlugs)) {
                $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id')->toArray();
                $post->tags()->sync($tagIds);
            }
        }

        $this->command->info('PostSeeder: ' . count($posts) . ' post berhasil di-seed.');
    }
}
