<?php

namespace App\Services;

class RobotsService
{
    /**
     * Generate robots.txt content.
     */
    public function generate(): string
    {
        // Check for override setting
        $override = SettingsService::get('robots_txt', '');
        if ($override) {
            return $override;
        }

        $lines = [];
        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        $lines[] = '';
        $lines[] = '# Disallow admin areas';
        $lines[] = 'Disallow: /admin';
        $lines[] = 'Disallow: /install';
        $lines[] = 'Disallow: /login';
        $lines[] = 'Disallow: /logout';
        $lines[] = 'Disallow: /register';
        $lines[] = 'Disallow: /profile';
        $lines[] = 'Disallow: /dashboard';
        $lines[] = 'Disallow: /bookings';
        $lines[] = '';
        $lines[] = '# Sitemap location';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        return implode("\n", $lines) . "\n";
    }
}
