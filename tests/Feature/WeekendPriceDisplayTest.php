<?php

namespace Tests\Feature;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the weekend "starting from" price display bug.
 *
 * The home-page cards, listing cards and property detail page previously used
 * Property::lowestPrice(), which returns the absolute minimum across BOTH the
 * weekday (_wd) and weekend (_we) keys — so the displayed "from" price never
 * changed on weekends. Property::lowestPriceToday() resolves the rate that
 * actually applies today (Asia/Jakarta), using the same weekday/weekend logic
 * as the detail-page pricing table, so weekend days show the weekend rate.
 */
class WeekendPriceDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeProperty(): Property
    {
        return Property::create([
            'name' => 'Weekend Price Property',
            'slug' => 'weekend-price-property',
            'status' => 'published',
            'unit_types' => ['studio'],
            'weekend_days' => [6, 0], // Sat, Sun
            'prices' => [
                'studio' => ['night_wd' => 500000, 'night_we' => 600000],
            ],
        ]);
    }

    /** On a weekday, the "starting from" price uses the weekday rate. */
    public function test_lowest_price_today_uses_weekday_rate_on_weekday(): void
    {
        // 2026-08-28 is a Friday (weekday under [6,0]) in Asia/Jakarta.
        Carbon::setTestNow(Carbon::create(2026, 8, 28, 10, 0, 0, 'Asia/Jakarta'));

        $property = $this->makeProperty();

        $this->assertSame(500000.0, $property->lowestPriceToday());
    }

    /** On a weekend, the "starting from" price switches to the weekend rate. */
    public function test_lowest_price_today_uses_weekend_rate_on_weekend(): void
    {
        // 2026-08-29 is a Saturday (weekend under [6,0]) in Asia/Jakarta.
        Carbon::setTestNow(Carbon::create(2026, 8, 29, 10, 0, 0, 'Asia/Jakarta'));

        $property = $this->makeProperty();

        $this->assertSame(600000.0, $property->lowestPriceToday());
    }

    /** Weekend rate falls back to the weekday value when no weekend price is set. */
    public function test_lowest_price_today_falls_back_when_weekend_unset(): void
    {
        // Saturday, but only a weekday rate exists.
        Carbon::setTestNow(Carbon::create(2026, 8, 29, 10, 0, 0, 'Asia/Jakarta'));

        $property = Property::create([
            'name' => 'Fallback Property',
            'slug' => 'fallback-property',
            'status' => 'published',
            'unit_types' => ['studio'],
            'weekend_days' => [6, 0],
            'prices' => [
                'studio' => ['night_wd' => 500000],
            ],
        ]);

        $this->assertSame(500000.0, $property->lowestPriceToday());
    }

    /** The property detail page renders the weekend rate on a weekend day. */
    public function test_detail_page_shows_weekend_price_on_weekend(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 29, 10, 0, 0, 'Asia/Jakarta'));

        $property = $this->makeProperty();

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('Rp 600.000');
    }
}
