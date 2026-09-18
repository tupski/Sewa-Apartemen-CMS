<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PlaceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $category = PlaceCategory::updateOrCreate(
            ['slug' => 'catering.cafe'],
            ['name_id' => 'Kafe Lama',
                'name_en' => 'Old Cafe',
                'is_active' => true]);

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

        $category = PlaceCategory::updateOrCreate(
            ['slug' => 'catering.cafe'],
            ['name_id' => 'Kafe',
                'name_en' => 'Cafe',
                'is_active' => true]);

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

        // A slug that is NOT in the shipped catalogue: adding it must create a
        // row. (Using a seeded slug would exercise the duplicate path instead,
        // since a migration now populates the catalogue.)
        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [$this->categoryPayload([
                'slug' => 'commercial.department_store',
                'name_id' => 'Department Store',
                'name_en' => 'Department Store',
            ])],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'created' => 1, 'updated' => 0]);

        $this->assertDatabaseHas('place_categories', [
            'slug' => 'commercial.department_store',
            'name_id' => 'Department Store',
            'name_en' => 'Department Store',
        ]);
    }

    public function test_duplicate_slugs_are_rejected(): void
    {
        $this->authenticate();

        $existing = PlaceCategory::updateOrCreate(
            ['slug' => 'catering.cafe'],
            ['name_id' => 'Kafe',
                'name_en' => 'Cafe',
                'is_active' => true]);

        // Two rows sharing one slug inside the payload → rejected by `distinct`.
        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [
                $this->categoryPayload(['id' => $existing->id]),
                $this->categoryPayload(['slug' => 'catering.cafe']),
            ],
        ]);

        $response->assertStatus(422);
        // Exactly one row for that slug — the duplicate was NOT inserted.
        $this->assertSame(1, PlaceCategory::where('slug', 'catering.cafe')->count());
    }

    public function test_a_new_category_cannot_collide_with_an_existing_slug(): void
    {
        $this->authenticate();

        PlaceCategory::updateOrCreate(
            ['slug' => 'catering.cafe'],
            ['name_id' => 'Kafe',
                'name_en' => 'Cafe',
                'is_active' => true]);

        $response = $this->postJson(route('admin.place-categories.update'), [
            'categories' => [
                $this->categoryPayload(['name_id' => 'Kafe Duplikat']),
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', fn (string $message) => str_contains($message, 'catering.cafe'));
        // Exactly one row for that slug — the colliding insert was rejected.
        $this->assertSame(1, PlaceCategory::where('slug', 'catering.cafe')->count());
    }

    public function test_delete_is_prevented_while_places_still_use_the_category(): void
    {
        $this->authenticate();

        $category = PlaceCategory::updateOrCreate(
            ['slug' => 'catering.cafe'],
            ['name_id' => 'Kafe',
                'name_en' => 'Cafe',
                'is_active' => true]);

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

        $category = PlaceCategory::updateOrCreate(
            ['slug' => 'sport'],
            ['name_id' => 'Olahraga',
                'name_en' => 'Sports',
                'is_active' => true]);

        $response = $this->deleteJson(route('admin.place-categories.destroy', $category));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('place_categories', ['id' => $category->id]);
    }

    public function test_category_management_requires_an_authenticated_admin(): void
    {
        $category = PlaceCategory::updateOrCreate(
            ['slug' => 'sport'],
            ['name_id' => 'Olahraga',
                'name_en' => 'Sports',
                'is_active' => true]);

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

    /* =================================================================
     | Geoapify slug validity
     * ================================================================= */

    /**
     * Every seeded slug is sent verbatim to the Geoapify Places API as the
     * `categories` filter. An unsupported slug makes the provider reject the
     * whole request with HTTP 400 "Category is not supported", so that POI
     * type is never synchronized and every sync reports it as failed
     * (observed in production with service.ambulance_station — the correct
     * Geoapify identifier is emergency.ambulance_station).
     *
     * This pins the shipped defaults to the slugs verified against the live
     * API. If Geoapify's taxonomy changes, this test failing is the signal to
     * re-verify and ship a rename migration.
     */
    public function test_no_seeded_category_slug_carries_a_stale_prefix(): void
    {
        foreach (PlaceCategorySeeder::defaults() as $category) {
            $this->assertStringStartsNotWith(
                'service.ambulance',
                $category['slug'],
                "'{$category['slug']} is not a valid Geoapify category — ambulances live under emergency.*."
            );
        }

        $slugs = array_column(PlaceCategorySeeder::defaults(), 'slug');

        $this->assertContains('emergency.ambulance_station', $slugs);
        $this->assertNotContains('service.ambulance_station', $slugs);
    }

    /**
     * The rename migration must heal an install that already seeded the broken
     * slug, and stay idempotent when run twice / after a re-seed.
     *
     * RefreshDatabase runs every pending migration during setUp, so the
     * migration has already executed by the time the test body runs. Roll it
     * back, seed the broken pre-fix state, then re-run it.
     */
    public function test_ambulance_slug_migration_renames_and_is_idempotent(): void
    {
        $path = 'database/migrations/2026_09_18_020228_rename_service_ambulance_station_slug.php';

        $this->artisan('migrate:rollback', ['--path' => $path, '--force' => true])->assertExitCode(0);

        // Rolling back the rename also renames the row the catalogue migration
        // seeded, so clear BOTH variants first: the pre-fix state is exactly
        // "the old slug exists, the new one does not".
        DB::table('place_categories')
            ->whereIn('slug', ['service.ambulance_station', 'emergency.ambulance_station'])
            ->delete();

        // Simulate the pre-fix DB state (with an admin-edited label).
        $oldId = DB::table('place_categories')->insertGetId([
            'slug' => 'service.ambulance_station',
            'name_id' => 'Stasiun Ambulans (edited)',
            'name_en' => 'Ambulance Station',
            'icon' => 'fa-solid fa-truck-medical',
            'color' => '#dc2626',
            'is_active' => true,
            'sort_order' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('migrate', ['--path' => $path, '--force' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('place_categories', ['slug' => 'service.ambulance_station']);
        $this->assertDatabaseHas('place_categories', [
            'slug' => 'emergency.ambulance_station',
            'name_id' => 'Stasiun Ambulans (edited)',
        ]);

        // Row identity (admin label edits) is preserved on rename.
        $this->assertSame($oldId, (int) DB::table('place_categories')->where('slug', 'emergency.ambulance_station')->value('id'));

        // Second run: nothing to do, no error.
        $this->artisan('migrate:rollback', ['--path' => $path, '--force' => true])->assertExitCode(0);
        $this->artisan('migrate', ['--path' => $path, '--force' => true])->assertExitCode(0);
        $this->assertDatabaseMissing('place_categories', ['slug' => 'service.ambulance_station']);
    }
}
