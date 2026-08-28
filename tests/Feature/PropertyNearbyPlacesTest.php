<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: saving nearby places from the admin property form used to fail
 * because PropertyRequest only allowed 4 categories while the form select
 * offered all 11 of Property::NEARBY_CATEGORIES (JS-added rows defaulted to
 * "Mall/Shopping"), so every save with a new place was rejected.
 */
class PropertyNearbyPlacesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function authenticate(): void
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    public function test_property_store_persists_nearby_places_for_every_category(): void
    {
        $this->authenticate();

        $places = [];
        foreach (array_keys(Property::NEARBY_CATEGORIES) as $i => $category) {
            $places[$i] = [
                'name' => 'Place ' . $i,
                'category' => $category,
                'lat' => '',
                'lng' => '',
            ];
        }
        // Blank row must be dropped server-side, not rejected.
        $places[] = ['name' => '', 'category' => 'Others', 'lat' => '', 'lng' => ''];

        $response = $this->post(route('admin.properties.store'), [
            'name' => 'Nearby Property',
            'slug' => 'nearby-property',
            'status' => 'published',
            'nearby_places' => $places,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.properties.index'));

        $property = Property::where('slug', 'nearby-property')->firstOrFail();
        $saved = $property->nearby_places;

        $this->assertCount(count(Property::NEARBY_CATEGORIES), $saved);
        $this->assertSame(
            array_keys(Property::NEARBY_CATEGORIES),
            array_column($saved, 'category')
        );
    }

    public function test_property_update_persists_nearby_places_with_coordinates(): void
    {
        $this->authenticate();

        $property = Property::create([
            'name' => 'Existing Property',
            'slug' => 'existing-property',
            'status' => 'published',
        ]);

        $response = $this->put(route('admin.properties.update', $property), [
            'name' => 'Existing Property',
            'slug' => 'existing-property',
            'status' => 'published',
            'nearby_places' => [
                ['name' => 'AEON Mall BSD', 'category' => 'Mall/Shopping', 'lat' => '-6.3020', 'lng' => '106.6520'],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $saved = $property->fresh()->nearby_places;
        $this->assertCount(1, $saved);
        $this->assertSame('AEON Mall BSD', $saved[0]['name']);
        $this->assertSame('Mall/Shopping', $saved[0]['category']);
        $this->assertEquals(-6.3020, $saved[0]['lat']);
        $this->assertEquals(106.6520, $saved[0]['lng']);
    }

    public function test_unknown_nearby_category_is_rejected(): void
    {
        $this->authenticate();

        $this->post(route('admin.properties.store'), [
            'name' => 'Bad Category',
            'slug' => 'bad-category',
            'status' => 'published',
            'nearby_places' => [
                ['name' => 'Somewhere', 'category' => 'Not A Category'],
            ],
        ])->assertSessionHasErrors('nearby_places.0.category');
    }
}
