<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for the frontend polish batch:
 *
 *  - the listing sort <select> must not do a full page reload (form.submit()
 *    fires no submit event, so Turbo never intercepts it),
 *  - the active duration/unit-type filter chip must paint the THEME colour,
 *  - the unit-type filter must offer only types that actually exist,
 *  - the result count wording, and the homepage stats/featured badge.
 */
class ListingUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SettingsService::clearCache();
        Setting::query()->delete();
    }

    private function published(array $attributes = []): Property
    {
        return Property::factory()->create(array_merge([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ], $attributes));
    }

    /* ===================================================================
     | Sort dropdown: Turbo-compatible submit
     * =================================================================== */

    public function test_sort_dropdown_submits_through_turbo_not_a_full_reload(): void
    {
        $html = $this->get(route('properties.public.index'))->assertOk()->getContent();

        // form.submit() bypasses the submit event entirely, so Turbo Drive
        // cannot intercept it and the browser reloads. requestSubmit() fires it.
        $this->assertStringNotContainsString('this.form.submit()', $html);
        $this->assertStringContainsString('this.form.requestSubmit()', $html);
    }

    /* ===================================================================
     | Filter chips use the theme colour
     * =================================================================== */

    public function test_active_filter_chips_use_the_configured_theme_colour(): void
    {
        SettingsService::set('primary_color', '#123456');
        SettingsService::clearCache();

        $html = $this->get(route('properties.public.index'))->assertOk()->getContent();

        // The chip carries the theme colour in a CSS variable that the checked
        // state consumes; Tailwind's peer-checked utility cannot express it.
        $this->assertStringContainsString('--chip-active: #123456', $html);
        $this->assertStringContainsString('listing-chip-label', $html);
    }

    /* ===================================================================
     | Unit-type filter lists only existing types
     * =================================================================== */

    public function test_unit_type_filter_offers_only_types_that_exist(): void
    {
        $this->published(['unit_types' => ['studio']]);

        $html = $this->get(route('properties.public.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="unit_type" value="studio"', $html);
        // Never offered: no published property has these.
        $this->assertStringNotContainsString('name="unit_type" value="penthouse"', $html);
        $this->assertStringNotContainsString('name="unit_type" value="4br"', $html);
    }

    public function test_unit_type_fieldset_is_hidden_when_no_types_exist(): void
    {
        $this->published(['unit_types' => null]);

        $html = $this->get(route('properties.public.index'))->assertOk()->getContent();

        // No canonical types at all => the whole fieldset is omitted rather than
        // rendering an empty "Tipe Unit" section.
        $this->assertStringNotContainsString('name="unit_type"', $html);
    }

    /* ===================================================================
     | Result count wording
     * =================================================================== */

    public function test_result_count_uses_the_location_wording(): void
    {
        $this->published();

        $html = $this->get(route('properties.public.index'))->assertOk()->getContent();

        $this->assertStringContainsString('lokasi apartemen ditemukan', $html);
        $this->assertStringNotContainsString('1 apartemen ditemukan', $html);
    }

    /* ===================================================================
     | Homepage: stats + featured badge
     * =================================================================== */

    public function test_homepage_stats_show_only_apartments_and_cities(): void
    {
        $this->published(['city' => 'Jakarta']);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString(__('home.stats_apartments'), $html);
        $this->assertStringContainsString(__('home.stats_cities'), $html);
        // The units column was removed from the hero.
        $this->assertStringNotContainsString(__('home.stats_units'), $html);
    }

    public function test_homepage_featured_badge_matches_the_listing_card_badge(): void
    {
        $this->published(['is_featured' => true]);

        $home = $this->get(route('home'))->assertOk()->getContent();
        $listing = $this->get(route('properties.public.index'))->assertOk()->getContent();

        // Both surfaces use the same yellow pill markup.
        $badge = 'bg-yellow-400/95';
        $this->assertStringContainsString($badge, $home);
        $this->assertStringContainsString($badge, $listing);

        // The old homepage-only white pill is gone.
        $this->assertStringNotContainsString('bg-white/95 text-xs font-bold px-3 py-1 rounded-full', $home);

        // The star is rendered once, from the template — not duplicated by lang.
        $this->assertStringNotContainsString('★ ★', $home);
    }

    /* ===================================================================
     | Card amenity overflow chip
     * =================================================================== */

    public function test_card_shows_an_overflow_chip_for_extra_amenities(): void
    {
        $property = $this->published();

        foreach (['AC', 'WIFI', 'Dapur', 'Kolam Renang', 'Gym'] as $name) {
            $amenity = Amenity::factory()->create(['name' => $name]);
            $property->amenities()->attach($amenity->id);
        }
        $property->load('amenities');

        $html = view('properties._card', ['property' => $property])->render();

        // 3 chips + 1 overflow chip ("2 lainnya").
        $this->assertStringContainsString('2 lainnya', $html);
        $this->assertStringContainsString('fa-solid fa-plus', $html);
    }
}
