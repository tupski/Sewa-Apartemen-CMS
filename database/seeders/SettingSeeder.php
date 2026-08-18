<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // General Settings
        $generalSettings = [
            'site_name' => 'Sewa Apartemen CMS',
            'site_description' => 'Professional apartment rental management system',
            'site_logo' => '',
            'site_favicon' => '',
            'contact_email' => 'info@sewaapartemen.com',
            'contact_phone' => '+62 21 1234 5678',
            'contact_address' => 'Jakarta, Indonesia',
            'site_tagline' => 'Quality Living in Premium Location',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'currency' => 'IDR',
            'whatsapp_default' => '',
        ];

        // Footer Settings
        $footerSettings = [
            'footer_about' => 'Sewa Apartemen CMS is a professional apartment rental management system that helps you manage your properties efficiently.',
            'footer_copyright' => '© 2026 Sewa Apartemen CMS. All rights reserved.',
            'social_facebook' => '',
            'social_twitter' => '',
            'social_instagram' => '',
            'social_linkedin' => '',
            'social_youtube' => '',
        ];

        // Homepage Settings (hero / CTA / features — kosongkan untuk memakai fallback blade)
        $homepageSettings = [
            'hero_title' => 'Temukan Apartemen Impian Anda',
            'hero_subtitle' => 'Ratusan pilihan apartemen harian, mingguan, dan bulanan dengan harga terbaik di lokasi premium.',
            'cta_title' => 'Siap Menyewa Apartemen?',
            'cta_text' => 'Hubungi kami sekarang untuk konsultasi gratis dan dapatkan penawaran terbaik untuk hunian Anda.',
            'cta_button_label' => 'Jelajahi Apartemen',
            'cta_button_url' => '',
            'features_title' => 'Kenapa Memilih Kami?',
            'features_subtitle' => 'Pengalaman menyewa apartemen yang mudah, aman, dan terpercaya.',
        ];

        // Theme Settings
        $themeSettings = [
            'primary_color' => '#3b82f6',
            'secondary_color' => '#10b981',
            'header_layout' => 'default',
            'footer_layout' => 'default',
            'enable_dark_mode' => '0',
            'accent_color' => '#F59E0B',
            'active_theme' => 'modern',
        ];

        // SEO Settings
        $seoSettings = [
            'meta_description' => 'Professional apartment rental management system for modern property management',
            'meta_keywords' => 'apartment, rental, property management, cms, booking',
            'google_analytics' => '',
            'facebook_pixel' => '',
        ];

        // Integrations Settings
        $integrationSettings = [
            'google_analytics_id' => '',
            'google_tag_manager_id' => '',
            'meta_pixel_id' => '',
            'search_console_token' => '',
            'microsoft_clarity_id' => '',
        ];

        // Insert general settings
        foreach ($generalSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'general',
                ]
            );
        }

        // Insert footer settings
        foreach ($footerSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'footer',
                ]
            );
        }

        // Insert homepage settings
        foreach ($homepageSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'homepage',
                ]
            );
        }

        // Insert theme settings
        foreach ($themeSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'theme',
                ]
            );
        }

        // Insert SEO settings
        foreach ($seoSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'seo',
                ]
            );
        }

        // Insert integration settings
        foreach ($integrationSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'integrations',
                ]
            );
        }
    }
}
