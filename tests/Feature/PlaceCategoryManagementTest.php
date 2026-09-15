<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin CRUD for the Artivo-managed place category catalogue
 * (the "Manage categories" panel on the property Create/Edit screen).
 *
 * Pins: bulk label editing (ID + EN), slug immutability on existing rows,
 * new-category creation, duplicate rejection, safe deletion (in-use
 * categories are protected), and admin-only authorization.
 */
class PlaceCategoryManagementTest extends TestCase
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

    protected function categoryPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => null,
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe',
            'name_en' => 'Cafe',
            'icon' => 'fa-solid fa-mug-hot',
            'color' => '#b45309',
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides);
    }

    public function test_admin_can_update_category_labels(): void
    {
        $this->authenticate();

        $category = PlaceCategory::create([
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe Lama',
            'name_en' => 'Old Cafe',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [
                $this->categoryPayload([
                    'id' => $category->id,
                    'name_id' => 'Kedai Kopi',
                    'name_en' => 'Coffee Shop',
                ]),
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'updated' => 1, 'created' => 0]);

        $category->refresh();
        $this->assertSame('Kedai Kopi', $category->name_id);
        $this->assertSame('Coffee Shop', $category->name_en);
    }

    public function test_slug_is_immutable_for_existing_categories(): void
    {
        $this->authenticate();

        $category = PlaceCategory::create([
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe',
            'name_en' => 'Cafe',
            'is_active' => true,
        ]);

        $this->postJson(route('admin.place-categories.update'), [
            'categories' => [
                $this->categoryPayload([
                    'id' => $category->id,
                    'slug' => 'commercial.shopping_mall',
                ]),
            ],
        ])->assertStatus(200);

        // The stored slug never changes — it is the sync match key.
        $this->assertSame('catering.cafe', $category->refresh()->slug);
        $this->assertDatabaseMissing('place_categories', ['slug' => 'commercial.shopping_mall']);
    }

    public function test_admin_can_add_a_new_category(): void
    {
        $this->authenticate();

        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [$this->categoryPayload()],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'created' => 1, 'updated' => 0]);

        $this->assertDatabaseHas('place_categories', [
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe',
            'name_en' => 'Cafe',
        ]);
    }

    public function test_duplicate_slugs_are_rejected(): void
    {
        $this->authenticate();

        $existing = PlaceCategory::create([
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe',
            'name_en' => 'Cafe',
            'is_active' => true,
        ]);

        // Two rows sharing one slug inside the payload → rejected by `distinct`.
        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [
                $this->categoryPayload(['id' => $existing->id]),
                $this->categoryPayload(['slug' => 'catering.cafe']),
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, PlaceCategory::count());
    }

    public function test_a_new_category_cannot_collide_with_an_existing_slug(): void
    {
        $this->authenticate();

        PlaceCategory::create([
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe',
            'name_en' => 'Cafe',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [
                $this->categoryPayload(['name_id' => 'Kafe Duplikat']),
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', fn (string $message) => str_contains($message, 'catering.cafe'));
        $this->assertSame(1, PlaceCategory::count());
    }

    public function test_delete_is_prevented_while_places_still_use_the_category(): void
    {
        $this->authenticate();

        $category = PlaceCategory::create([
            'slug' => 'catering.cafe',
            'name_id' => 'Kafe',
            'name_en' => 'Cafe',
            'is_active' => true,
        ]);

        Place::create([
            'geoapify_place_id' => 'gp-1',
            'name' => 'Kopi Kenangan',
            'category' => 'catering.cafe',
            'lat' => -6.2,
            'lng' => 106.8,
            'fetched_at' => now(),
        ]);

        $response = $this->deleteJson(route('admin.place-categories.destroy', $category));

        $response->assertStatus(422);
        $this->assertDatabaseHas('place_categories', ['id' => $category->id]);
    }

    public function test_an_unused_category_can_be_deleted(): void
    {
        $this->authenticate();

        $category = PlaceCategory::create([
            'slug' => 'sport',
            'name_id' => 'Olahraga',
            'name_en' => 'Sports',
            'is_active' => true,
        ]);

        $response = $this->deleteJson(route('admin.place-categories.destroy', $category));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('place_categories', ['id' => $category->id]);
    }

    public function test_category_management_requires_an_authenticated_admin(): void
    {
        $category = PlaceCategory::create([
            'slug' => 'sport',
            'name_id' => 'Olahraga',
            'name_en' => 'Sports',
            'is_active' => true,
        ]);

        // Guest → redirected to login.
        $this->post(route('admin.place-categories.update'), ['categories' => []])
            ->assertRedirect(route('login'));

        // Authenticated non-admin → forbidden.
        $this->actingAs($this->user)
            ->postJson(route('admin.place-categories.update'), ['categories' => []])
            ->assertForbidden();

        $this->actingAs($this->user)
            ->deleteJson(route('admin.place-categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('place_categories', ['id' => $category->id]);
    }
}
