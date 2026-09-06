<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Property;
use Illuminate\Support\Facades\Cache;

class SitemapService
{
    /**
     * Generate cached sitemap XML content.
     */
    public function generate(): string
    {
        return Cache::remember('sitemap.xml', 86400, fn (): string => $this->buildXml());
    }

    /**
     * Build the raw XML sitemap.
     */
    protected function buildXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($this->collectUrls() as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($url['loc'])."</loc>\n";
            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>'.e($url['lastmod'])."</lastmod>\n";
            }
            if (isset($url['changefreq'])) {
                $xml .= '    <changefreq>'.e($url['changefreq'])."</changefreq>\n";
            }
            if (isset($url['priority'])) {
                $xml .= '    <priority>'.e($url['priority'])."</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        return $xml.'</urlset>';
    }

    /**
     * Collect all indexable public URLs using named routes and configurable slugs.
     *
     * @return array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
     */
    protected function collectUrls(): array
    {
        $now = now()->toIso8601String();
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('properties.public.index'),
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'loc' => route('blog.index'),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            [
                'loc' => route('promotions'),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            [
                'loc' => route('contact'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
        ];

        foreach (Property::published()->get(['slug', 'updated_at']) as $property) {
            $urls[] = [
                'loc' => route('properties.public.show', $property),
                'lastmod' => $property->updated_at?->toIso8601String() ?? $now,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach (Page::published()->get(['slug', 'updated_at']) as $page) {
            $urls[] = [
                'loc' => url('/'.$page->slug),
                'lastmod' => $page->updated_at?->toIso8601String() ?? $now,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        foreach (Post::published()->get(['slug', 'updated_at']) as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at?->toIso8601String() ?? $now,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }
}
