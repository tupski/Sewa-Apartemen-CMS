<?php

namespace App\Services;

use App\Models\SeoMetadata;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Truncate title to max 60 chars, append site name.
     */
    public static function title(string $title): string
    {
        $siteName = SettingsService::get('site_name', config('app.name', ''));
        $maxLen = 60;

        if ($siteName && !str_contains($title, $siteName)) {
            $separator = ' | ';
            $maxTitleLen = $maxLen - strlen($separator) - strlen($siteName);
            $title = Str::limit($title, $maxTitleLen, '');
            return $title . $separator . $siteName;
        }

        return Str::limit($title, $maxLen, '');
    }

    /**
     * Truncate description to max 160 chars.
     */
    public static function description(string $description): string
    {
        return Str::limit($description, 160, '');
    }

    /**
     * Build canonical URL.
     */
    public static function canonical(string $url): string
    {
        return url($url);
    }

    /**
     * Render Open Graph tags as HTML string.
     */
    public static function openGraphTags(array $data): string
    {
        $tags = '';
        $defaults = [
            'title' => SettingsService::get('site_name', config('app.name')),
            'description' => '',
            'image' => '',
            'url' => url()->current(),
            'type' => 'website',
        ];
        $data = array_merge($defaults, $data);

        if ($data['title']) {
            $tags .= '<meta property="og:title" content="' . e($data['title']) . '">' . "\n";
        }
        if ($data['description']) {
            $tags .= '<meta property="og:description" content="' . e($data['description']) . '">' . "\n";
        }
        if ($data['image']) {
            $tags .= '<meta property="og:image" content="' . e($data['image']) . '">' . "\n";
        }
        $tags .= '<meta property="og:url" content="' . e($data['url']) . '">' . "\n";
        $tags .= '<meta property="og:type" content="' . e($data['type']) . '">' . "\n";

        return $tags;
    }

    /**
     * Render Twitter card tags as HTML string.
     */
    public static function twitterTags(array $data): string
    {
        $tags = '';
        $defaults = [
            'title' => SettingsService::get('site_name', config('app.name')),
            'description' => '',
            'image' => '',
        ];
        $data = array_merge($defaults, $data);

        $tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        if ($data['title']) {
            $tags .= '<meta name="twitter:title" content="' . e($data['title']) . '">' . "\n";
        }
        if ($data['description']) {
            $tags .= '<meta name="twitter:description" content="' . e($data['description']) . '">' . "\n";
        }
        if ($data['image']) {
            $tags .= '<meta name="twitter:image" content="' . e($data['image']) . '">' . "\n";
        }

        return $tags;
    }

    /**
     * Render all meta tags + JSON-LD from a seoable model or array.
     */
    public static function renderMetaTags($source): string
    {
        $seoData = static::metaTagsArray($source);

        $html = '';
        $html .= '<title>' . e($seoData['title']) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . e($seoData['description']) . '">' . "\n";
        if ($seoData['canonical']) {
            $html .= '<link rel="canonical" href="' . e($seoData['canonical']) . '">' . "\n";
        }
        $html .= '<meta name="robots" content="' . e($seoData['robots']) . '">' . "\n";
        $html .= static::openGraphTags($seoData);
        $html .= static::twitterTags($seoData);
        $html .= static::renderJsonLd($seoData['jsonld'] ?? []);

        return $html;
    }

    /**
     * Build meta tags array from a model or array.
     */
    public static function metaTagsArray($source): array
    {
        if (is_array($source)) {
            return static::fromArray($source);
        }

        if (is_object($source) && method_exists($source, 'seo')) {
            return static::fromSeoable($source);
        }

        return static::defaults();
    }

    /**
     * Legacy alias: metaTags() for view sharing.
     */
    public static function metaTags($title, $description = '', $url = '', $image = '', $type = 'website'): array
    {
        return [
            'title' => static::title($title),
            'description' => static::description($description),
            'canonical' => $url ? static::canonical($url) : url()->current(),
            'image' => $image,
            'robots' => 'index, follow',
            'type' => $type,
            'jsonld' => [
                SchemaService::organization(),
                SchemaService::website(),
            ],
        ];
    }

    protected static function fromSeoable($model): array
    {
        $seo = $model->seo;

        $title = $model->name ?? $model->title ?? '';
        $description = $model->description ?? $model->excerpt ?? '';

        if ($seo) {
            $title = $seo->meta_title ?: $title;
            $description = $seo->meta_description ?: $description;
        }

        $canonical = $seo?->canonical_url ?? url()->current();
        $robots = ($seo && !$seo->index_status) ? 'noindex, follow' : 'index, follow';

        $og = $seo?->open_graph ?? [];
        $tw = $seo?->twitter ?? [];

        return [
            'title' => static::title($title),
            'description' => static::description($description),
            'canonical' => $canonical,
            'image' => $og['image'] ?? $tw['image'] ?? '',
            'robots' => $robots,
            'type' => $og['type'] ?? 'website',
            'jsonld' => static::buildJsonLdForModel($model),
        ];
    }

    protected static function fromArray(array $data): array
    {
        return [
            'title' => static::title($data['title'] ?? ''),
            'description' => static::description($data['description'] ?? ''),
            'canonical' => $data['canonical'] ?? url()->current(),
            'image' => $data['image'] ?? '',
            'robots' => $data['robots'] ?? 'index, follow',
            'type' => $data['type'] ?? 'website',
            'jsonld' => $data['jsonld'] ?? [],
        ];
    }

    protected static function defaults(): array
    {
        return [
            'title' => static::title(SettingsService::get('site_name', config('app.name', ''))),
            'description' => static::description(SettingsService::get('site_description', '')),
            'canonical' => url()->current(),
            'image' => '',
            'robots' => 'index, follow',
            'type' => 'website',
            'jsonld' => [
                SchemaService::organization(),
                SchemaService::website(),
            ],
        ];
    }

    protected static function buildJsonLdForModel($model): array
    {
        $schemas = [
            SchemaService::organization(),
            SchemaService::website(),
        ];

        $class = get_class($model);

        if ($class === \App\Models\Property::class) {
            $schemas[] = SchemaService::realEstateListing($model);
        } elseif ($class === \App\Models\Unit::class) {
            $schemas[] = SchemaService::realEstateListing($model->property ?? $model);
            $schemas[] = SchemaService::offer($model);
        }

        return $schemas;
    }

    /**
     * Render JSON-LD script tags for schema arrays.
     */
    public static function renderJsonLd(array $schemas): string
    {
        $html = '';
        foreach ($schemas as $schema) {
            if (empty($schema)) continue;
            // Check if this is already a string or nested schema
            $clean = array_filter((array) $schema);
            if (empty($clean)) continue;
            // If first key is numeric, it's a list of schemas — recurse
            if (array_keys($clean)[0] === 0 || is_int(array_keys($clean)[0])) {
                $html .= static::renderJsonLd($clean);
                continue;
            }
            $html .= '<script type="application/ld+json">' . "\n"
                   . json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
                   . '</script>' . "\n";
        }
        return $html;
    }
}
