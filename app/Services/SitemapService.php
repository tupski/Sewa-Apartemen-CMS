<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;

class SitemapService
{
    /**
     * Generate sitemap XML content.
     */
    public function generate(): string
    {
        return Cache::remember('sitemap.xml', 86400, function () {
            return $this->buildXml();
        });
    }

    /**
     * Build the raw XML sitemap.
     */
    protected function buildXml(): string
    {
        $urls = $this->collectUrls();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($url['loc']) . "</loc>\n";
            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }
            if (isset($url['changefreq'])) {
                $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            }
            if (isset($url['priority'])) {
                $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Collect all sitemap URLs.
     */
    protected function collectUrls(): array
    {
        $urls = [];

        // Static routes
        $urls[] = [
            'loc' => url('/'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Properties
        foreach (Property::where('status', 'published')->get() as $property) {
            $urls[] = [
                'loc' => url('/'),
                'lastmod' => $property->updated_at->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // Units
        foreach (Unit::where('status', 'available')->get() as $unit) {
            $urls[] = [
                'loc' => url('/'),
                'lastmod' => $unit->updated_at->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // Pages
        foreach (Page::where('status', 'published')->get() as $page) {
            $urls[] = [
                'loc' => url('/' . $page->slug),
                'lastmod' => $page->updated_at->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // Blog
        if (class_exists(\App\Models\Post::class)) {
            $urls[] = [
                'loc' => url('/blog'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];

            $postModel = \App\Models\Post::class;
            foreach ($postModel::where('status', 'published')->get() as $post) {
                $urls[] = [
                    'loc' => url('/blog/' . ($post->slug ?? $post->id)),
                    'lastmod' => $post->updated_at->toIso8601String(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        }

        return $urls;
    }
}
