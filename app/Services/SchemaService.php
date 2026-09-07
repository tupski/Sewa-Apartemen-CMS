<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Property;
use Illuminate\Support\Str;

class SchemaService
{
    /**
     * Organization schema.
     */
    public static function organization(): array
    {
        $siteName = (string) SettingsService::get('site_name', config('app.name', ''));
        $logo = trim((string) SettingsService::get('site_logo', ''));
        $sameAs = array_values(array_filter([
            SettingsService::get('social_instagram', ''),
            SettingsService::get('social_facebook', ''),
            SettingsService::get('social_twitter', ''),
            SettingsService::get('social_linkedin', ''),
            SettingsService::get('social_youtube', ''),
        ]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $siteName,
            'url' => url('/'),
        ];

        if ($logo !== '') {
            $schema['logo'] = SeoService::absoluteImageUrl($logo);
        }

        if ($sameAs !== []) {
            $schema['sameAs'] = $sameAs;
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
            '@id' => url('/').'#website',
            'name' => SettingsService::get('site_name', config('app.name', '')),
            'url' => url('/'),
            'publisher' => ['@id' => url('/').'#organization'],
        ];
    }

    /**
     * WebPage schema for non-record public routes.
     */
    public static function webPage(string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'description' => Str::limit(strip_tags($description), 300),
            'url' => $url,
            'isPartOf' => ['@id' => url('/').'#website'],
        ];
    }

    /**
     * CollectionPage and ItemList schema for apartment/blog indexes.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function collectionPage(string $name, string $description, string $url, array $items = []): array
    {
        $schema = self::webPage($name, $description, $url);
        $schema['@type'] = 'CollectionPage';

        if ($items !== []) {
            $schema['mainEntity'] = [
                '@type' => 'ItemList',
                'itemListElement' => array_values($items),
            ];
        }

        return $schema;
    }

    /**
     * RealEstateListing schema for a published property.
     */
    public static function realEstateListing(Property $property, ?string $url = null): array
    {
        $url ??= route('properties.public.show', $property);
        $description = Str::limit(strip_tags((string) ($property->description ?? '')), 300);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            '@id' => $url.'#listing',
            'name' => (string) ($property->name ?? ''),
            'description' => $description,
            'url' => $url,
        ];

        $image = $property->featuredImage?->url ?: optional($property->photos->first())->media?->url;
        if ($image) {
            $schema['image'] = [$image];
        }

        if ($property->address || $property->city || $property->province || $property->postal_code) {
            $address = ['@type' => 'PostalAddress'];
            if ($property->address) {
                $address['streetAddress'] = $property->address;
            }
            if ($property->city) {
                $address['addressLocality'] = $property->city;
            }
            if ($property->province) {
                $address['addressRegion'] = $property->province;
            }
            if ($property->postal_code) {
                $address['postalCode'] = $property->postal_code;
            }
            $address['addressCountry'] = 'ID';
            $schema['address'] = $address;
        }

        if ($property->latitude !== null && $property->longitude !== null) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $property->latitude,
                'longitude' => (float) $property->longitude,
            ];
        }

        $lowestPrice = $property->lowestPrice();
        if ($lowestPrice !== null && $lowestPrice > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'url' => $url,
                'price' => $lowestPrice,
                'priceCurrency' => 'IDR',
                'availability' => 'https://schema.org/InStock',
            ];
        }

        if ($property->amenities->isNotEmpty()) {
            $schema['amenityFeature'] = $property->amenities
                ->map(fn ($amenity): array => [
                    '@type' => 'LocationFeatureSpecification',
                    'name' => (string) $amenity->name,
                    'value' => true,
                ])->values()->all();
        }

        return $schema;
    }

    /**
     * Promotion page schema.
     */
    public static function promotionPage(string $name, string $description, string $url, iterable $vouchers = []): array
    {
        $offers = [];
        foreach ($vouchers as $voucher) {
            $offer = [
                '@type' => 'Offer',
                'name' => (string) $voucher->name,
                'description' => (string) $voucher->code,
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
            ];
            if ($voucher->valid_until) {
                $offer['validThrough'] = $voucher->valid_until->toDateString();
            }
            $offers[] = $offer;
        }

        $schema = self::collectionPage($name, $description, $url);
        $schema['mainEntity'] = [
            '@type' => 'OfferCatalog',
            'name' => $name,
            'itemListElement' => $offers,
        ];

        return $schema;
    }

    /**
     * Contact page schema backed by configured business contact details.
     */
    public static function contactPage(string $name, string $description, string $url): array
    {
        $organization = self::organization();
        $contactPoint = ['@type' => 'ContactPoint', 'contactType' => 'customer service'];
        $email = trim((string) SettingsService::get('contact_email', ''));
        $phone = trim((string) SettingsService::get('contact_phone', ''));

        if ($email !== '') {
            $contactPoint['email'] = $email;
        }
        if ($phone !== '') {
            $contactPoint['telephone'] = $phone;
        }
        if (count($contactPoint) > 2) {
            $organization['contactPoint'] = $contactPoint;
        }

        $address = trim((string) SettingsService::get('contact_address', ''));
        if ($address !== '') {
            $organization['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'ID',
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => $name,
            'description' => Str::limit(strip_tags($description), 300),
            'url' => $url,
            'mainEntity' => $organization,
        ];
    }

    /**
     * Article schema from a persisted Post.
     */
    public static function postArticle(Post $post, ?string $url = null): array
    {
        $url ??= route('blog.show', $post->slug);
        $schema = self::article([
            'headline' => $post->title,
            'description' => $post->excerpt ?: Str::limit(strip_tags((string) $post->content), 300),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => $post->author?->name,
        ]);
        $schema['url'] = $url;
        $schema['mainEntityOfPage'] = ['@type' => 'WebPage', '@id' => $url];
        $schema['publisher'] = ['@id' => url('/').'#organization'];

        if ($post->featured_image) {
            $schema['image'] = [SeoService::absoluteImageUrl($post->featured_image)];
        }

        $wordCount = str_word_count(
            html_entity_decode(strip_tags((string) $post->content), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
        if ($wordCount > 0) {
            $schema['wordCount'] = $wordCount;
        }

        if (filled($post->category?->name)) {
            $schema['articleSection'] = (string) $post->category->name;
        }

        $about = $post->tags
            ->map(fn ($tag): string => (string) $tag->name)
            ->filter()
            ->values();
        if ($about->isNotEmpty()) {
            $schema['about'] = $about->all();
        }

        return $schema;
    }

    /**
     * BreadcrumbList schema.
     *
     * @param  array<string, string>|array<int, array{name: string, url: string}>  $items
     */
    public static function breadcrumbList(array $items): array
    {
        $listItems = [];
        $position = 1;
        foreach ($items as $name => $url) {
            if (is_array($url)) {
                $name = $url['name'] ?? '';
                $url = $url['url'] ?? '';
            }
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * Article schema from a data array, retained for existing callers/tests.
     *
     * @param  array<string, mixed>  $data
     */
    public static function article(array $data): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => (string) ($data['headline'] ?? ''),
            'description' => Str::limit(strip_tags((string) ($data['description'] ?? '')), 300),
        ];

        foreach (['datePublished', 'dateModified'] as $date) {
            if (! empty($data[$date])) {
                $schema[$date] = $data[$date];
            }
        }
        if (! empty($data['author'])) {
            $schema['author'] = ['@type' => 'Person', 'name' => $data['author']];
        }

        return $schema;
    }
}
