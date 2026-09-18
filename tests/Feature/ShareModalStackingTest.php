<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The share dialog opened from the What's Around card ("Bagikan petunjuk arah")
 * rendered BEHIND the Leaflet map.
 *
 * Leaflet assigns its own z-indexes inside the map container: tile/overlay panes
 * 200–400, shadow 500, marker 600, tooltip 650, popup 700, and controls up to
 * 1000. The share modal sat at z-[70] — below all of them — so it painted
 * underneath the map. It now uses the app's topmost overlay level, and the map
 * container isolates its own stacking context so Leaflet can never escape it.
 */
class ShareModalStackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($this->user);
    }

    /**
     * Every `z-[N]` value the app gives a top-level overlay (modal/dialog).
     *
     * @return array<int, int>
     */
    private function overlayZIndexes(string $html): array
    {
        preg_match_all('/\bz-\[(\d+)\]/', $html, $m);

        return array_map('intval', $m[1]);
    }

    public function test_share_modal_sits_above_every_leaflet_layer(): void
    {
        // Leaflet's highest internal z-index among its controls/panes.
        $leafletMax = 1000;

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $html = $this->get(route('properties.public.show', $property))
            ->assertStatus(200)
            ->getContent();

        // Locate the share modal root specifically, not any other overlay.
        $pos = strpos($html, 'x-data="shareModal()"');
        $this->assertNotFalse($pos, 'The share modal was not rendered on the property page.');

        $tag = substr($html, $pos, 600);
        $this->assertMatchesRegularExpression(
            '/z-\[(\d+)\]/',
            $tag,
            'The share modal root must declare an explicit stacking level.'
        );

        preg_match('/z-\[(\d+)\]/', $tag, $m);
        $shareZ = (int) $m[1];

        $this->assertGreaterThan(
            $leafletMax,
            $shareZ,
            "The share dialog (z-{$shareZ}) must sit above Leaflet's deepest layer (z-{$leafletMax}) or it renders behind the map."
        );
    }

    public function test_modal_overlays_share_one_topmost_z_index(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $html = $this->get(route('properties.public.show', $property))
            ->assertStatus(200)
            ->getContent();

        // The share modal and the confirm modal are both page-level blocking
        // overlays; divergent values are how the share dialog ended up below
        // the map in the first place. They must agree.
        $pos = strpos($html, 'x-data="shareModal()"');
        preg_match('/z-\[(\d+)\]/', substr($html, $pos, 600), $share);
        preg_match_all('/z-\[(\d+)\]/', $html, $all);

        $this->assertNotEmpty($all[1]);
        $max = max(array_map('intval', $all[1]));

        $this->assertSame(
            $max,
            (int) $share[1],
            'Blocking overlays must share the topmost z-index; a lower value hides the dialog behind the map.'
        );
    }

    public function test_map_container_isolates_its_stacking_context(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $html = $this->get(route('properties.public.show', $property))
            ->assertStatus(200)
            ->getContent();

        // Without `isolate`, Leaflet's internal z-indexes (up to 1000) compete
        // directly with page overlays, which is the root cause of the bug.
        $this->assertMatchesRegularExpression(
            '/id="property-map"[^>]*class="[^"]*\bisolate\b[^"]*"/',
            $html,
            'The map container must create its own stacking context so Leaflet cannot paint over page overlays.'
        );
    }
}
