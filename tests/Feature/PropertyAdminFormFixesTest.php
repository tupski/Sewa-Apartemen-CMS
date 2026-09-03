<?php

namespace Tests\Feature;

use App\Jobs\FetchNearbyPlacesJob;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Regression cover for three admin property-form defects reported from production:
 *
 *  1. The POI "Resync" control was a <form> nested inside the main property
 *     <form>. Browsers discard the inner form, so clicking it submitted the
 *     OUTER update form and navigated to the property list without ever syncing.
 *  2. The pricing tables were only rendered server-side when a room type was
 *     already selected, so on the Create screen ticking a room type revealed no
 *     price inputs at all.
 *  3. The Nearby Places (Geoapify) section was missing entirely from Create.
 *
 * Also pins the toast <template> fix: Alpine clones a template's first element
 * child, so a bare emoji text node produced a null clone and threw
 * "Cannot set properties of null (setting '_x_dataStack')", which killed every
 * Alpine component on the page.
 */
class PropertyAdminFormFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        config()->set('services.geoapify.key', 'test-key');
        config()->set('services.geoapify.map_key', 'test-map-key');
    }

    protected function authenticate(): void
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    private function propertyWithCoords(array $attributes = []): Property
    {
        return Property::factory()->create(array_merge([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ], $attributes));
    }

    /**
     * Count `<form` tags from the main property form onwards.
     *
     * The admin layout renders its own forms (logout, locale, currency) in the
     * header — i.e. BEFORE @yield('content') — and none after it, so slicing at
     * the property form isolates the content region. Any count above 1 means a
     * partial re-introduced a nested form.
     */
    private function formsInsidePropertyForm(string $html): int
    {
        $start = strpos($html, '<form id="property-form"');

        $this->assertNotFalse($start, 'The main property form was not rendered.');

        return substr_count(substr($html, $start), '<form');
    }

    /* =================================================================
     | 1. Resync control must not be a nested form
     * ================================================================= */

    public function test_edit_page_resync_control_is_not_a_nested_form(): void
    {
        $this->authenticate();

        $property = $this->propertyWithCoords();
        $resyncUrl = route('admin.properties.resync-nearby-places', $property);

        $response = $this->get(route('admin.properties.edit', $property));

        $response->assertStatus(200);

        // The resync URL is still on the page (as a fetch target)...
        $response->assertSee($resyncUrl, false);
        $response->assertSee('data-url="'.$resyncUrl.'"', false);

        // ...but never as a <form action>, which is what caused the navigation.
        $response->assertDontSee('action="'.$resyncUrl.'"', false);

        // The property form must be the only form in the content region.
        $this->assertSame(
            1,
            $this->formsInsidePropertyForm($response->getContent()),
            'The property edit form must not contain a nested <form> element.'
        );
    }

    public function test_resync_via_xhr_returns_json_and_does_not_redirect(): void
    {
        Queue::fake();
        $this->authenticate();

        $property = $this->propertyWithCoords();

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'message', 'count', 'html']);

        Queue::assertPushed(FetchNearbyPlacesJob::class);
    }

    public function test_resync_via_xhr_without_coordinates_returns_error_json(): void
    {
        Queue::fake();
        $this->authenticate();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        Queue::assertNotPushed(FetchNearbyPlacesJob::class);
    }

    public function test_resync_without_xhr_still_redirects_back_with_flash(): void
    {
        Queue::fake();
        $this->authenticate();

        $property = $this->propertyWithCoords();

        $response = $this->post(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        Queue::assertPushed(FetchNearbyPlacesJob::class);
    }

    /* =================================================================
     | 2. Pricing rows must exist on Create so ticking a type reveals them
     * ================================================================= */

    public function test_create_page_renders_price_inputs_for_every_room_type(): void
    {
        $this->authenticate();

        $response = $this->get(route('admin.properties.create'));

        $response->assertStatus(200);
        $response->assertSee('id="price-tables"', false);
        $response->assertSee('id="price-empty"', false);

        foreach (array_keys(Property::UNIT_TYPES) as $type) {
            $response->assertSee('name="prices['.$type.'][night_wd]"', false);
            $response->assertSee('name="prices['.$type.'][monthly]"', false);
            $response->assertSee('name="prices['.$type.'][t3_wd]"', false);
            $response->assertSee('name="prices['.$type.'][weekly]"', false);
        }
    }

    public function test_create_page_hides_price_tables_until_a_type_is_ticked(): void
    {
        $this->authenticate();

        $content = $this->get(route('admin.properties.create'))
            ->assertStatus(200)
            ->getContent();

        // Nothing selected yet: tables hidden, hint visible.
        $this->assertStringContainsString('<div id="price-tables" class="hidden">', $content);
        $this->assertMatchesRegularExpression(
            '#<div id="price-empty"\s+class="[^"]*(?<!hidden)"#',
            $content,
            'The "tick a room type" hint must be visible when no type is selected.'
        );
    }

    public function test_edit_page_shows_price_tables_when_types_are_already_selected(): void
    {
        $this->authenticate();

        $property = $this->propertyWithCoords([
            'unit_types' => ['studio'],
            'prices' => ['studio' => ['night_wd' => 250000]],
        ]);

        $content = $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('<div id="price-tables" class="">', $content);
        $this->assertStringContainsString('id="price-empty"', $content);
        $this->assertStringContainsString('250000', $content);
    }

    /* =================================================================
     | 3. Nearby Places section on Create
     * ================================================================= */

    public function test_create_page_renders_the_nearby_places_section(): void
    {
        $this->authenticate();

        $response = $this->get(route('admin.properties.create'));

        $response->assertStatus(200);
        $response->assertSee('Tempat Terdekat (Geoapify)', false);
        $response->assertSee('id="poi-table-wrap"', false);
        // No property id yet, so syncing is explicitly unavailable — not faked.
        $response->assertSee('Simpan properti terlebih dahulu', false);
        $response->assertDontSee('data-url=', false);
    }

    public function test_create_page_nearby_section_does_not_add_a_second_form(): void
    {
        $this->authenticate();

        $content = $this->get(route('admin.properties.create'))
            ->assertStatus(200)
            ->getContent();

        $this->assertSame(
            1,
            $this->formsInsidePropertyForm($content),
            'The property create form must not contain a nested <form> element.'
        );
    }

    /* =================================================================
     | 4. Alpine toast templates must wrap content in an element
     * ================================================================= */

    public function test_admin_toast_templates_wrap_icons_in_an_element(): void
    {
        $this->authenticate();

        $content = $this->get(route('admin.properties.index'))
            ->assertStatus(200)
            ->getContent();

        foreach (['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'] as $type => $icon) {
            $this->assertStringContainsString(
                '<template x-if="toast.type === \''.$type.'\'"><span>'.$icon.'</span></template>',
                $content,
                "The {$type} toast icon must be wrapped in a single root element."
            );
        }
    }
}
