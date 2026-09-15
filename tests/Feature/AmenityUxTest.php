<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 — Amenity UX: server-side search, pagination/load-more, and the
 * property-form picker that must never download the full amenity dataset.
 */
class AmenityUxTest extends TestCase
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

    public function test_index_search_is_server_side(): void
    {
        $this->authenticate();
        Amenity::factory()->create(['name' => 'Kolam Renang Infinity']);
        Amenity::factory()->create(['name' => 'Parkir Basement']);

        $response = $this->get(route('admin.amenities.index', ['search' => 'kolam']));

        $response->assertStatus(200);
        $response->assertSee('Kolam Renang Infinity');
        $response->assertDontSee('Parkir Basement');
    }

    public function test_index_search_is_case_insensitive(): void
    {
        $this->authenticate();
        Amenity::factory()->create(['name' => 'Kolam Renang']);

        $this->get(route('admin.amenities.index', ['search' => 'KOLAM']))
            ->assertStatus(200)
            ->assertSee('Kolam Renang');
    }

    public function test_index_pagination_preserves_filters(): void
    {
        $this->authenticate();
        // 20 matching + 10 non-matching: page 2 of the filtered set must keep
        // the search filter (withQueryString) and still exclude non-matches.
        Amenity::factory()->count(20)->create(['name' => 'Wifi']);
        Amenity::factory()->count(10)->sequence(fn ($s) => ['name' => 'Other '.$s->index])->create();

        $response = $this->get(route('admin.amenities.index', ['search' => 'wifi', 'page' => 2]));

        $response->assertStatus(200);
        $page = $response->viewData('amenities');
        $this->assertSame(2, $page->currentPage());
        $this->assertSame(20, $page->total());
        $this->assertCount(5, $page->items());
        $this->assertEveryItemIs($page->items(), 'Wifi');
        // Rendered links keep the search query.
        $response->assertSee('search=wifi', false);
    }

    public function test_index_no_results_state_offers_reset(): void
    {
        $this->authenticate();
        Amenity::factory()->create(['name' => 'Kolam Renang']);

        $this->get(route('admin.amenities.index', ['search' => 'zzz-no-match']))
            ->assertStatus(200)
            ->assertSee('No amenities match your filters')
            ->assertSee('Reset Filters')
            ->assertDontSee('Get started by creating a new amenity');
    }

    public function test_options_endpoint_paginates_without_counting_all(): void
    {
        $this->authenticate();
        Amenity::factory()->count(25)->sequence(fn ($s) => ['name' => 'Amenity '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT)])->create();
        Amenity::factory()->inactive()->create(['name' => 'Hidden Amenity']);

        $first = $this->getJson(route('admin.amenities.options'));
        $first->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('page', 1)
            ->assertJsonPath('has_more', true);
        $first->assertDontSee('Hidden Amenity');

        $second = $this->getJson(route('admin.amenities.options', ['page' => 2]));
        $second->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('has_more', false);
    }

    public function test_options_endpoint_search_is_server_side_and_case_insensitive(): void
    {
        $this->authenticate();
        Amenity::factory()->create(['name' => 'Kolam Renang Dewasa']);
        Amenity::factory()->create(['name' => 'Parkir Luas']);

        $this->getJson(route('admin.amenities.options', ['search' => 'kolam']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kolam Renang Dewasa')
            ->assertJsonPath('has_more', false);
    }

    public function test_options_endpoint_rejects_bad_page(): void
    {
        $this->authenticate();

        $this->getJson(route('admin.amenities.options', ['page' => 0]))->assertStatus(422);
    }

    public function test_property_edit_form_does_not_embed_full_dataset(): void
    {
        $this->authenticate();
        $property = Property::factory()->create();
        $picked = Amenity::factory()->create(['name' => 'Sudah Dipilih']);
        $property->amenities()->sync([$picked->id]);
        Amenity::factory()->count(30)->sequence(fn ($s) => ['name' => 'Banyak Sekali '.$s->index])->create();

        $response = $this->get(route('admin.properties.edit', $property));

        $response->assertStatus(200);
        // Only the selection is server-rendered as a form input; the rest is
        // streamed from the options endpoint (first page = 20 of 31).
        $response->assertSee('Sudah Dipilih');
        $response->assertDontSee('Banyak Sekali 30');
        $response->assertSee('amenities'.'\\/'.'options', false);
    }

    public function test_property_store_still_syncs_amenities_array(): void
    {
        $this->authenticate();
        $amenities = Amenity::factory()->count(3)->create();

        $payload = Property::factory()->raw([
            'slug' => 'sync-test-prop',
            'amenities' => $amenities->pluck('id')->all(),
        ]);
        unset($payload['id']);

        $response = $this->post(route('admin.properties.store'), $payload);
        $response->assertStatus(302);

        $property = Property::where('slug', 'sync-test-prop')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $amenities->pluck('id')->all(),
            $property->amenities()->pluck('amenities.id')->all()
        );
    }

    public function test_options_requires_admin(): void
    {
        // Guest → login redirect (plain GET; JSON would 401 via `expectsJson`).
        $this->get(route('admin.amenities.options'))->assertRedirect(route('login'));

        // Authenticated non-admin → forbidden.
        $this->actingAs($this->user);
        $this->getJson(route('admin.amenities.options'))->assertForbidden();
    }

    /**
     * @param  array<int, Amenity>  $items
     */
    private function assertEveryItemIs(array $items, string $name): void
    {
        foreach ($items as $item) {
            $this->assertSame($name, $item->name);
        }
    }
}
