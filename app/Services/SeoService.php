<?php

namespace App\Services;

use App\Models\Property;
use App\Models\SeoMetadata;
use App\Models\SystemPage;
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
     * @param  string  $title  Base page title (without the site-name suffix).
     * @param  bool  $isHomepage  When true, uses the homepage "{Site Name} - {Tagline}" format.
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
            $suffix = $separator.$siteName;
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
        return url('storage/'.ltrim($image, '/'));
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
            // Optional social-only overrides (set by admin SEO editors). When
            // empty these fall back to the page title/description above.
            'og_title' => null,
            'og_description' => null,
        ];
        $data = array_merge($defaults, $data);

        $image = static::absoluteImageUrl($data['image']);

        $ogTitle = $data['og_title'] ?: $data['title'];
        $ogDescription = $data['og_description'] ?: $data['description'];

        if ($ogTitle) {
            $tags .= '<meta property="og:title" content="'.e($ogTitle).'">'."\n";
        }
        if ($ogDescription) {
            $tags .= '<meta property="og:description" content="'.e($ogDescription).'">'."\n";
        }
        if ($image) {
            $tags .= '<meta property="og:image" content="'.e($image).'">'."\n";
            $tags .= '<meta property="og:image:alt" content="'.e($ogTitle).'">'."\n";
        }
        $tags .= '<meta property="og:url" content="'.e($data['url']).'">'."\n";
        $tags .= '<meta property="og:type" content="'.e($data['type']).'">'."\n";
        if ($data['site_name']) {
            $tags .= '<meta property="og:site_name" content="'.e($data['site_name']).'">'."\n";
        }
        $tags .= '<meta property="og:locale" content="'.e(str_replace('-', '_', app()->getLocale())).'">'."\n";

        // Product price tags (only meaningful when type=product and a price is set).
        if ($data['type'] === 'product' && $data['price_amount'] !== null && $data['price_amount'] !== '') {
            $tags .= '<meta property="product:price:amount" content="'.e((string) $data['price_amount']).'">'."\n";
            $tags .= '<meta property="product:price:currency" content="'.e((string) ($data['price_currency'] ?: 'IDR')).'">'."\n";
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
            // Optional Twitter-only overrides; fall back to OG, then the page
            // title/description.
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'og_title' => null,
            'og_description' => null,
        ];
        $data = array_merge($defaults, $data);

        $image = static::absoluteImageUrl($data['twitter_image'] ?: $data['image']);

        $title = $data['twitter_title'] ?: ($data['og_title'] ?: $data['title']);
        $description = $data['twitter_description'] ?: ($data['og_description'] ?: $data['description']);

        $tags .= '<meta name="twitter:card" content="'.($image ? 'summary_large_image' : 'summary').'">'."\n";
        if ($title) {
            $tags .= '<meta name="twitter:title" content="'.e($title).'">'."\n";
        }
        if ($description) {
            $tags .= '<meta name="twitter:description" content="'.e($description).'">'."\n";
        }
        if ($image) {
            $tags .= '<meta name="twitter:image" content="'.e($image).'">'."\n";
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
        $html .= '<title>'.e($seoData['title']).'</title>'."\n";
        $html .= '<meta name="description" content="'.e($seoData['description']).'">'."\n";
        if ($seoData['canonical']) {
            $html .= '<link rel="canonical" href="'.e($seoData['canonical']).'">'."\n";
        }
        $html .= '<meta name="robots" content="'.e($seoData['robots']).'">'."\n";
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

    /**
     * Fetch the admin-managed SEO override for a non-CMS route.
     *
     * Routes such as the homepage, the apartment listing, the blog index, the
     * contact page and the promotions page have no Eloquent record of their own,
     * so admins manage their metadata through `SystemPage` (a registry row) plus
     * the shared `seo_metadata` morph. Returns null when the registry row or the
     * metadata row does not exist yet, letting the caller keep its hardcoded
     * defaults.
     *
     * Deliberately NOT memoized in a static: `system_pages` holds a handful of
     * rows and each page render resolves at most one key, while a process-level
     * cache would go stale between requests under a persistent worker (and
     * between tests). Degrades to null — never throws — when the `system_pages`
     * table has not been migrated yet on an existing install.
     */
    public static function systemPageOverride(string $key): ?SeoMetadata
    {
        try {
            return SystemPage::with('seo')->where('key', $key)->first()?->seo;
        } catch (\Throwable $e) {
            // Table missing (migration not run yet) — fall back to defaults.
            return null;
        }
    }

    /**
     * Build a meta-tag array for a non-CMS route, applying the admin override
     * from `SystemPage` on top of the caller's defaults.
     *
     * Placeholders (e.g. `:name`, `:city`) let template routes such as the
     * apartment detail page reuse one override across every record.
     *
     * @param  string  $key  SystemPage registry key.
     * @param  string  $title  Fallback base title (no site-name suffix).
     * @param  string  $description  Fallback description.
     * @param  string  $url  Canonical URL.
     * @param  array<string, string>  $placeholders  Replacement map, e.g. ['name' => 'Skyhouse BSD'].
     * @param  array<string, mixed>  $extra  Extra keys merged into the result (image, type, price_amount, ...).
     */
    public static function forSystemPage(
        string $key,
        string $title,
        string $description = '',
        string $url = '',
        array $placeholders = [],
        array $extra = []
    ): array {
        $override = static::systemPageOverride($key);
        $hasTitleOverride = (bool) ($override && $override->meta_title);

        if ($override) {
            $title = $override->meta_title ?: $title;
            $description = $override->meta_description ?: $description;
        }

        if ($placeholders !== []) {
            $title = static::replacePlaceholders($title, $placeholders);
            $description = static::replacePlaceholders($description, $placeholders);
        }

        $og = $override?->open_graph ?? [];
        $tw = $override?->twitter ?? [];

        $meta = static::metaTags($title, $description, $url);

        // metaTags() applies the "{Site Name} - {Tagline}" homepage format, which
        // would otherwise discard an admin-authored homepage title. An explicit
        // override always wins; title() still appends the site name (and strips a
        // duplicate suffix if the admin typed one).
        if ($hasTitleOverride) {
            $meta['title'] = static::title($title);
        }

        if ($override) {
            if ($override->canonical_url) {
                $meta['canonical'] = $override->canonical_url;
            }
            $meta['robots'] = $override->index_status ? 'index, follow' : 'noindex, follow';
            if (! empty($og['image']) || ! empty($tw['image'])) {
                $meta['image'] = $og['image'] ?? $tw['image'];
            }
            if (! empty($og['type'])) {
                $meta['type'] = $og['type'];
            }

            // Social-only overrides (blank => reuse title/description).
            $meta['og_title'] = static::maybePlaceholders($og['title'] ?? null, $placeholders);
            $meta['og_description'] = static::maybePlaceholders($og['description'] ?? null, $placeholders);
            $meta['twitter_title'] = static::maybePlaceholders($tw['title'] ?? null, $placeholders);
            $meta['twitter_description'] = static::maybePlaceholders($tw['description'] ?? null, $placeholders);
            $meta['twitter_image'] = $tw['image'] ?? null;
        }

        return array_merge($meta, $extra);
    }

    /**
     * Replace `:placeholder` tokens in an admin-authored string.
     *
     * @param  array<string, string>  $placeholders
     */
    public static function replacePlaceholders(string $value, array $placeholders): string
    {
        foreach ($placeholders as $token => $replacement) {
            $value = str_replace(':'.ltrim($token, ':'), (string) $replacement, $value);
        }

        return $value;
    }

    /**
     * Null-safe {@see static::replacePlaceholders()} for optional fields.
     *
     * @param  array<string, string>  $placeholders
     */
    protected static function maybePlaceholders(?string $value, array $placeholders): ?string
    {
        if ($value === null || $value === '' || $placeholders === []) {
            return $value;
        }

        return static::replacePlaceholders($value, $placeholders);
    }

    /**
     * Build meta tags for a property detail page.
     *
     * Precedence (highest first):
     *   1. The property's OWN `seo_metadata` morph (per-listing override).
     *   2. The `properties.show` SystemPage template, with `:name` / `:city` /
     *      `:province` / `:price` placeholders resolved per listing.
     *   3. The property's `name` / `description` columns.
     *
     * @param  Property  $property
     */
    public static function forPropertyDetail($property): array
    {
        $seo = $property->seo;

        $template = static::systemPageOverride('properties.show');
        $placeholders = [];

        // Only consult the template for fields the listing has not overridden.
        $templateTitle = (! $seo || ! $seo->meta_title) ? ($template?->meta_title ?: null) : null;
        $templateDescription = (! $seo || ! $seo->meta_description) ? ($template?->meta_description ?: null) : null;

        if ($templateTitle !== null || $templateDescription !== null) {
            $lowest = $property->lowestPrice();
            $placeholders = [
                'name' => (string) ($property->name ?? ''),
                'city' => (string) ($property->city ?? ''),
                'province' => (string) ($property->province ?? ''),
                'price' => $lowest !== null && $lowest > 0
                    ? 'Rp '.number_format($lowest, 0, ',', '.')
                    : '',
            ];
        }

        return static::fromSeoable($property, [
            'title' => $templateTitle !== null ? static::replacePlaceholders($templateTitle, $placeholders) : null,
            'description' => $templateDescription !== null ? static::replacePlaceholders($templateDescription, $placeholders) : null,
        ]);
    }

    /**
     * @param  array{title: ?string, description: ?string}  $templateFallbacks
     *                                                                          Values used INSTEAD of the model's own columns when the model has
     *                                                                          no explicit SEO override for that field. Used by
     *                                                                          {@see static::forPropertyDetail()} to apply a SystemPage template.
     */
    protected static function fromSeoable($model, array $templateFallbacks = []): array
    {
        $seo = $model->seo;

        $title = $templateFallbacks['title'] ?? null ?: ($model->name ?? $model->title ?? '');
        $description = $templateFallbacks['description'] ?? null ?: ($model->description ?? $model->excerpt ?? '');

        if ($seo) {
            $title = $seo->meta_title ?: $title;
            $description = $seo->meta_description ?: $description;
        }

        $canonical = $seo?->canonical_url ?? url()->current();
        $robots = ($seo && ! $seo->index_status) ? 'noindex, follow' : 'index, follow';

        $og = $seo?->open_graph ?? [];
        $tw = $seo?->twitter ?? [];

        // Property-specific enrichment: main photo, price into description, product OG.
        $image = $og['image'] ?? $tw['image'] ?? '';
        $type = $og['type'] ?? 'website';
        $priceAmount = null;
        $priceCurrency = null;

        if ($model instanceof Property) {
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
                $priceLabel = 'Mulai dari Rp '.number_format($lowest, 0, ',', '.');
                $baseDesc = trim(strip_tags((string) $description));
                $description = $baseDesc !== ''
                    ? $priceLabel.'. '.$baseDesc
                    : $priceLabel.' — '.$title;
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
            // Social-only overrides from the morph; null means "reuse the page
            // title/description" (handled in openGraphTags()/twitterTags()).
            'og_title' => $og['title'] ?? null,
            'og_description' => $og['description'] ?? null,
            'twitter_title' => $tw['title'] ?? null,
            'twitter_description' => $tw['description'] ?? null,
            'twitter_image' => $tw['image'] ?? null,
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
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_title' => $data['twitter_title'] ?? null,
            'twitter_description' => $data['twitter_description'] ?? null,
            'twitter_image' => $data['twitter_image'] ?? null,
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

        if ($class === Property::class) {
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
            if (empty($schema)) {
                continue;
            }
            // Check if this is already a string or nested schema
            $clean = array_filter((array) $schema);
            if (empty($clean)) {
                continue;
            }
            // If first key is numeric, it's a list of schemas — recurse
            if (array_keys($clean)[0] === 0 || is_int(array_keys($clean)[0])) {
                $html .= static::renderJsonLd($clean);

                continue;
            }
            $html .= '<script type="application/ld+json">'."\n"
                   .json_encode(
                       $clean,
                       JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                       | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                   )."\n"
                   .'</script>'."\n";
        }

        return $html;
    }
}
