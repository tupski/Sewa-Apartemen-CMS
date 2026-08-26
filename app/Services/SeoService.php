<?php

namespace App\Services;

use App\Models\SeoMetadata;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Build the standardized <title> string.
     *
     * Rules:
     *  - Homepage:      "{Site Name} - {Tagline}"  (tagline = Site Description)
     *                   Falls back to just "{Site Name}" when the tagline is empty.
     *  - Other pages:   "{Page Title} - {Site Name}"
     *
     * The method normalizes titles so the site name is never duplicated: if the
     * incoming title already ends with a " - {Site Name}" (or legacy " | {Site Name}")
     * suffix — as older controllers/SEO metadata may provide — that suffix is
     * stripped before the standardized one is re-applied.
     *
     * @param string $title      Base page title (without the site-name suffix).
     * @param bool   $isHomepage When true, uses the homepage "{Site Name} - {Tagline}" format.
     */
    public static function title(string $title, bool $isHomepage = false): string
    {
        $siteName = trim((string) SettingsService::get('site_name', config('app.name', '')));

        // Homepage: "{Site Name} - {Tagline}" (tagline = Site Description).
        if ($isHomepage) {
            $tagline = trim((string) SettingsService::get('site_description', ''));

            return $tagline !== '' ? "{$siteName} - {$tagline}" : $siteName;
        }

        $title = trim($title);

        if ($title === '') {
            return $siteName;
        }

        if ($siteName === '') {
            return $title;
        }

        // Strip an existing site-name suffix to avoid double-appending
        // (handles both the new " - " separator and the legacy " | " one).
        foreach ([' - ', ' | '] as $separator) {
            $suffix = $separator . $siteName;
            if (Str::endsWith($title, $suffix)) {
                $title = trim(Str::beforeLast($title, $suffix));
                break;
            }
        }

        // If the base title collapses to the site name itself, don't duplicate.
        if ($title === '' || $title === $siteName) {
            return $siteName;
        }

        return "{$title} - {$siteName}";
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
     * Detect whether the current request is the homepage.
     *
     * Uses the named route `home` when available and falls back to comparing
     * the current URL against the site root so it also works in contexts where
     * the route name is unavailable (e.g. console-driven rendering).
     */
    public static function isHomepage(): bool
    {
        try {
            if (request()->routeIs('home')) {
                return true;
            }
        } catch (\Throwable $e) {
            // Ignore — fall through to URL comparison.
        }

        return rtrim(url()->current(), '/') === rtrim(url('/'), '/');
    }

    /**
     * Resolve any image reference (Media URL, storage-relative path, or absolute
     * URL) into an ABSOLUTE URL suitable for Open Graph / Twitter previews.
     *
     * When no image is provided, falls back to a sitewide OG image
     * (`site_og_image` setting) and then to the site logo, so shared links
     * always show a preview image.
     */
    public static function absoluteImageUrl(?string $image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            // Sitewide fallback: dedicated OG image, then the site logo.
            $fallback = trim((string) SettingsService::get('site_og_image', ''))
                ?: trim((string) SettingsService::get('site_logo', ''));

            if ($fallback === '') {
                return '';
            }

            $image = $fallback;
        }

        // Already absolute.
        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        // Root-relative URL (e.g. Storage::url() output like "/storage/...").
        if (Str::startsWith($image, '/')) {
            return url($image);
        }

        // Bare storage-relative path (e.g. a setting storing "logos/logo.png").
        return url('storage/' . ltrim($image, '/'));
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
            'url' => $data['canonical'] ?? url()->current(),
            'type' => 'website',
            'site_name' => SettingsService::get('site_name', config('app.name')),
            'price_amount' => null,
            'price_currency' => null,
        ];
        $data = array_merge($defaults, $data);

        $image = static::absoluteImageUrl($data['image']);

        if ($data['title']) {
            $tags .= '<meta property="og:title" content="' . e($data['title']) . '">' . "\n";
        }
        if ($data['description']) {
            $tags .= '<meta property="og:description" content="' . e($data['description']) . '">' . "\n";
        }
        if ($image) {
            $tags .= '<meta property="og:image" content="' . e($image) . '">' . "\n";
            $tags .= '<meta property="og:image:alt" content="' . e($data['title']) . '">' . "\n";
        }
        $tags .= '<meta property="og:url" content="' . e($data['url']) . '">' . "\n";
        $tags .= '<meta property="og:type" content="' . e($data['type']) . '">' . "\n";
        if ($data['site_name']) {
            $tags .= '<meta property="og:site_name" content="' . e($data['site_name']) . '">' . "\n";
        }
        $tags .= '<meta property="og:locale" content="' . e(str_replace('-', '_', app()->getLocale())) . '">' . "\n";

        // Product price tags (only meaningful when type=product and a price is set).
        if ($data['type'] === 'product' && $data['price_amount'] !== null && $data['price_amount'] !== '') {
            $tags .= '<meta property="product:price:amount" content="' . e((string) $data['price_amount']) . '">' . "\n";
            $tags .= '<meta property="product:price:currency" content="' . e((string) ($data['price_currency'] ?: 'IDR')) . '">' . "\n";
        }

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

        $image = static::absoluteImageUrl($data['image']);

        $tags .= '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
        if ($data['title']) {
            $tags .= '<meta name="twitter:title" content="' . e($data['title']) . '">' . "\n";
        }
        if ($data['description']) {
            $tags .= '<meta name="twitter:description" content="' . e($data['description']) . '">' . "\n";
        }
        if ($image) {
            $tags .= '<meta name="twitter:image" content="' . e($image) . '">' . "\n";
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
            'title' => static::title($title, static::isHomepage()),
            'description' => static::description($description),
            'canonical' => $url ? static::canonical($url) : url()->current(),
            'image' => $image,
            'robots' => 'index, follow',
            'type' => $type,
            'site_name' => SettingsService::get('site_name', config('app.name')),
            'price_amount' => null,
            'price_currency' => null,
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

        // Property-specific enrichment: main photo, price into description, product OG.
        $image = $og['image'] ?? $tw['image'] ?? '';
        $type = $og['type'] ?? 'website';
        $priceAmount = null;
        $priceCurrency = null;

        if ($model instanceof \App\Models\Property) {
            // Prefer featured image, then first gallery photo, for a rich preview.
            if ($image === '') {
                $image = $model->featuredImage?->url
                    ?: optional($model->photos->first())->media?->url
                    ?: '';
            }

            $lowest = $model->lowestPrice();
            if ($lowest !== null && $lowest > 0) {
                $priceAmount = (int) $lowest;
                $priceCurrency = 'IDR';
                $type = $og['type'] ?? 'product';

                // Fold the price into the description ("Mulai dari Rp X").
                $priceLabel = 'Mulai dari Rp ' . number_format($lowest, 0, ',', '.');
                $baseDesc = trim(strip_tags((string) $description));
                $description = $baseDesc !== ''
                    ? $priceLabel . '. ' . $baseDesc
                    : $priceLabel . ' — ' . $title;
            }
        }

        return [
            'title' => static::title($title),
            'description' => static::description(strip_tags((string) $description)),
            'canonical' => $canonical,
            'image' => $image,
            'robots' => $robots,
            'type' => $type,
            'site_name' => SettingsService::get('site_name', config('app.name')),
            'price_amount' => $priceAmount,
            'price_currency' => $priceCurrency,
            'jsonld' => static::buildJsonLdForModel($model),
        ];
    }

    protected static function fromArray(array $data): array
    {
        return [
            'title' => static::title($data['title'] ?? ''),
            'description' => static::description(strip_tags((string) ($data['description'] ?? ''))),
            'canonical' => $data['canonical'] ?? url()->current(),
            'image' => $data['image'] ?? '',
            'robots' => $data['robots'] ?? 'index, follow',
            'type' => $data['type'] ?? 'website',
            'site_name' => $data['site_name'] ?? SettingsService::get('site_name', config('app.name')),
            'price_amount' => $data['price_amount'] ?? null,
            'price_currency' => $data['price_currency'] ?? null,
            'jsonld' => $data['jsonld'] ?? [],
        ];
    }

    protected static function defaults(): array
    {
        return [
            'title' => static::title(SettingsService::get('site_name', config('app.name', '')), static::isHomepage()),
            'description' => static::description(SettingsService::get('site_description', '')),
            'canonical' => url()->current(),
            'image' => '',
            'robots' => 'index, follow',
            'type' => 'website',
            'site_name' => SettingsService::get('site_name', config('app.name')),
            'price_amount' => null,
            'price_currency' => null,
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
                   . json_encode(
                       $clean,
                       JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                       | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                   ) . "\n"
                   . '</script>' . "\n";
        }
        return $html;
    }
}
