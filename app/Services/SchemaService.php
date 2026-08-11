<?php

namespace App\Services;

class SchemaService
{
    /**
     * Organization schema.
     */
    public static function organization(): array
    {
        $siteName = SettingsService::get('site_name', config('app.name', ''));
        $siteUrl = url('/');
        $logo = SettingsService::get('site_logo', '');
        $logoUrl = $logo ? url('storage/' . $logo) : '';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $siteUrl,
        ];

        if ($logoUrl) {
            $schema['logo'] = $logoUrl;
        }

        $sameAs = array_filter([
            SettingsService::get('social_instagram', ''),
            SettingsService::get('social_facebook', ''),
            SettingsService::get('social_twitter', ''),
            SettingsService::get('social_linkedin', ''),
            SettingsService::get('social_youtube', ''),
        ]);

        if ($sameAs) {
            $schema['sameAs'] = array_values($sameAs);
        }

        return $schema;
    }

    /**
     * WebSite schema.
     */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => SettingsService::get('site_name', config('app.name', '')),
            'url' => url('/'),
        ];
    }

    /**
     * RealEstateListing schema from Property or Unit.
     */
    public static function realEstateListing($model): array
    {
        // If it's a Unit, use the property for listing
        if ($model instanceof \App\Models\Unit) {
            $model = $model->property;
        }

        if (!$model) {
            return [];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $model->name ?? '',
            'description' => \Illuminate\Support\Str::limit(strip_tags($model->description ?? ''), 300),
            'url' => url('/'),
        ];

        if ($model->city || $model->province) {
            $address = ['@type' => 'PostalAddress'];
            if ($model->address) $address['streetAddress'] = $model->address;
            if ($model->city) $address['addressLocality'] = $model->city;
            if ($model->province) $address['addressRegion'] = $model->province;
            if ($model->postal_code) $address['postalCode'] = $model->postal_code;
            $address['addressCountry'] = 'ID';
            $schema['address'] = $address;
        }

        return $schema;
    }

    /**
     * Offer schema for a Unit.
     */
    public static function offer($unit): array
    {
        $price = $unit->price_per_month ?? $unit->price_per_night ?? $unit->price_per_year ?? 0;
        $currency = SettingsService::get('currency', 'IDR');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Offer',
            'name' => $unit->name ?? '',
            'description' => \Illuminate\Support\Str::limit(strip_tags($unit->description ?? ''), 300),
            'price' => (string) $price,
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/InStock',
        ];
    }

    /**
     * BreadcrumbList schema.
     */
    public static function breadcrumbList(array $items): array
    {
        $listItems = [];
        $position = 1;

        foreach ($items as $name => $url) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
                'item' => $url,
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * Article schema (for blog posts — guard: only if Post/BlogPost model exists).
     */
    public static function article(array $data): array
    {
        if (!class_exists(\App\Models\BlogPost::class) && !class_exists(\App\Models\Post::class)) {
            return [];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $data['headline'] ?? '',
            'description' => $data['description'] ?? '',
        ];

        if (isset($data['datePublished'])) {
            $schema['datePublished'] = $data['datePublished'];
        }
        if (isset($data['dateModified'])) {
            $schema['dateModified'] = $data['dateModified'];
        }
        if (isset($data['author'])) {
            $schema['author'] = ['@type' => 'Person', 'name' => $data['author']];
        }

        return $schema;
    }
}
