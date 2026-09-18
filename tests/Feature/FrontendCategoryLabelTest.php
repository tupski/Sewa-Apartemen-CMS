<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\Property;
use App\Models\PropertyPlace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A deeply nested provider category (catering.cafe.coffee_shop) must never
 * leak to the public property page — neither as a "What's Around" group
 * heading nor on the map card / filter chips. It resolves to the nearest
 * catalogue slug (catering.cafe) and renders that row's localized label.
 */
class FrontendCategoryLabelTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalogue(): void
    {
        $rows = [
            ['slug' => 'catering.cafe', 'name_id' => 'Kafe', 'name_en' => 'Cafe'],
            ['slug' => 'tourism', 'name_id' => 'Wisata', 'name_en' => 'Tourism'],
        ];

        foreach ($rows as $i => $row) {
            PlaceCategory::updateOrCreate(
                ['slug' => $row['slug']],
                $row + ['icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => $i]
            );
        }
    }

    private function propertyWithDeepChainPoi(): Property
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $place = Place::create([
            'geoapify_place_id' => 'gp-deep-chain-1',
            'name' => 'Cofilab Deep Chain',
            'category' => 'catering.cafe.coffee_shop',
            'lat' => -6.21,
            'lng' => 106.81,
            'address' => 'Jl. Kopi No. 1',
            'raw_category' => 'catering.cafe.coffee_shop',
            'fetched_at' => now(),
        ]);

        PropertyPlace::create([
            'property_id' => $property->id,
            'place_id' => $place->id,
            'source' => 'geoapify',
            'distance_m' => 250,
        ]);

        return $property->refresh();
    }

    private function mapPayload(string $html): array
    {
        if (! preg_match('#<script type="application/json" id="map-data">(.*?)</script>#s', $html, $m)) {
            $this->fail('The #map-data JSON block was not rendered.');
        }

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    #[DataProvider('localeProvider')]
    public function test_deep_chain_renders_the_catalogue_label(string $locale, string $expectedLabel): void
    {
        $this->seedCatalogue();
        $property = $this->propertyWithDeepChainPoi();

        // The locale is resolved per request (LocaleMiddleware reads the
        // session first), so drive it through the session, not setLocale().
        $content = $this->withSession(['locale' => $locale])
            ->get(route('properties.public.show', $property))
            ->assertStatus(200)
            ->getContent();

        // No raw chain anywhere in the document.
        $this->assertStringNotContainsString('catering.cafe.coffee_shop', $content);

        // The list groups under the catalogue label.
        $this->assertStringContainsString($expectedLabel, $content);

        // The map payload carries the resolved label for cards + filter chips.
        $map = $this->mapPayload($content);
        $pois = array_values(array_filter($map['markers'], fn ($m) => ($m['type'] ?? '') === 'poi'));

        $this->assertNotEmpty($pois, 'Expected at least one POI marker in #map-data.');
        $this->assertSame($expectedLabel, $pois[0]['cat_label']);
    }

    public static function localeProvider(): array
    {
        return [
            'Indonesian' => ['id', 'Kafe'],
            'English' => ['en', 'Cafe'],
        ];
    }

    public function test_normalized_slug_prefers_the_nearest_catalogue_ancestor(): void
    {
        $this->seedCatalogue();

        $this->assertSame('catering.cafe', PlaceCategory::normalizedSlug('catering.cafe.coffee_shop'));
        $this->assertSame('tourism', PlaceCategory::normalizedSlug('tourism.attraction.viewpoint'));
        $this->assertNull(PlaceCategory::normalizedSlug('commercial.shopping_mall'));
        $this->assertNull(PlaceCategory::normalizedSlug(null));
        $this->assertNull(PlaceCategory::normalizedSlug(''));
    }
}
