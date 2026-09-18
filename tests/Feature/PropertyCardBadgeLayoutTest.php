<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for two defects on the property-card overlay + nearby section:
 *
 *  1. The share icon and the unit-type badge were BOTH absolutely positioned at
 *     `top-3 right-3`, so they rendered on top of each other. They now share one
 *     flex row, which makes overlap structurally impossible.
 *
 *  2. The distance badge read a bare "1.7 km" (dot decimal, no context). On a
 *     card that is itself a property, the number is meaningless without saying
 *     what it is measured from. It now reads "1,7 Km dari <current property>",
 *     with the distance SOURCE passed from the page being viewed.
 */
class PropertyCardBadgeLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function nearbySectionHtml(): string
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'name' => 'Apartemen Induk',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        // A second property with coordinates so buildNearbyProperties() returns it.
        Property::factory()->create([
            'status' => 'published',
            'name' => 'Apartemen Tetangga',
            'latitude' => -6.215,
            'longitude' => 106.815,
            'unit_types' => ['studio'],
        ]);

        return $this->get(route('properties.public.show', $property))
            ->assertStatus(200)
            ->getContent();
    }

    public function test_share_button_and_type_badge_do_not_share_a_position(): void
    {
        $html = $this->nearbySectionHtml();

        // The type badge must live inside the same flex row as the share button,
        // never as its own top-3 right-3 overlay.
        $this->assertStringNotContainsString(
            'absolute top-3 right-3 px-2 py-1 rounded-lg text-xs font-medium text-white/90 bg-black/40',
            $html,
            'The unit-type badge must not be absolutely positioned at the same spot as the share button.'
        );

        $this->assertMatchesRegularExpression(
            '/class="absolute top-3 right-3 flex items-center gap-2"/',
            $html,
            'The share button and type badge must share one top-right flex row.'
        );
    }

    public function test_distance_badge_names_the_property_it_is_measured_from(): void
    {
        $html = $this->nearbySectionHtml();

        // Comma decimal separator (Indonesian) + the source property name.
        $this->assertMatchesRegularExpression(
            '/\d+,\d+\s*Km dari Apartemen Induk/u',
            $html,
            'The distance badge must read "<n,n> Km dari <current property>".'
        );

        // The old bare, dot-decimal form must be gone.
        $this->assertDoesNotMatchRegularExpression(
            '/>\s*\d+\.\d+\s*km\s*</',
            $html,
            'The distance badge must not fall back to the bare "1.7 km" form.'
        );

        // The location pin still leads the badge.
        $this->assertStringContainsString('fa-location-dot', $html);
    }

    public function test_nearby_section_heading_uses_the_current_property_name(): void
    {
        $html = $this->nearbySectionHtml();

        $this->assertStringContainsString('Akomodasi terdekat dari Apartemen Apartemen Induk', $html);
    }
}
