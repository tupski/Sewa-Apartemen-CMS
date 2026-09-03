<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Property;
use App\Models\Role;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the per-entity SEO override pipeline.
 *
 * Pages previously posted FLAT `meta_title` / `meta_description` inputs while
 * PageController persisted only nested `seo[...]` values, so every page SEO
 * override was silently discarded. These tests pin the nested contract for
 * Pages and re-assert the already-working Property path end to end.
 */
class SeoOverrideTest extends TestCase
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

    public function test_page_store_persists_seo_to_morph(): void
    {
        $this->authenticate();

        $response = $this->post(route('admin.pages.store'), [
            'title' => 'Tentang Kami',
            'slug' => 'tentang-kami',
            'content' => '<p>Profil perusahaan.</p>',
            'status' => 'published',
            'seo' => [
                'meta_title' => 'Tentang Lya Rooms',
                'meta_description' => 'Kenali tim dan layanan sewa apartemen kami.',
            ],
        ]);

        $response->assertRedirect(route('admin.pages.index'));

        $page = Page::where('slug', 'tentang-kami')->firstOrFail();

        $this->assertNotNull($page->seo);
        $this->assertSame('Tentang Lya Rooms', $page->seo->meta_title);
        $this->assertSame('Kenali tim dan layanan sewa apartemen kami.', $page->seo->meta_description);
    }

    public function test_page_update_persists_seo_to_morph(): void
    {
        $this->authenticate();

        $page = Page::create([
            'user_id' => $this->user->id,
            'title' => 'Kebijakan',
            'slug' => 'kebijakan',
            'content' => '<p>Awal.</p>',
            'status' => 'published',
        ]);

        $response = $this->put(route('admin.pages.update', $page), [
            'title' => 'Kebijakan Privasi',
            'slug' => 'kebijakan',
            'content' => '<p>Diperbarui.</p>',
            'status' => 'published',
            'seo' => [
                'meta_title' => 'Kebijakan Privasi Lya Rooms',
                'meta_description' => 'Bagaimana kami menangani data tamu.',
            ],
        ]);

        $response->assertRedirect(route('admin.pages.index'));

        $page->refresh();

        $this->assertNotNull($page->seo);
        $this->assertSame('Kebijakan Privasi Lya Rooms', $page->seo->meta_title);
        $this->assertSame('Bagaimana kami menangani data tamu.', $page->seo->meta_description);
    }

    public function test_page_edit_form_exposes_nested_seo_inputs(): void
    {
        $this->authenticate();

        $page = Page::create([
            'user_id' => $this->user->id,
            'title' => 'FAQ',
            'slug' => 'faq',
            'content' => '<p>Pertanyaan.</p>',
            'status' => 'published',
        ]);

        $page->seo()->updateOrCreate([], [
            'meta_title' => 'FAQ Meta Title Tersimpan',
            'meta_description' => 'FAQ meta description tersimpan.',
        ]);

        $response = $this->get(route('admin.pages.edit', $page));

        $response->assertStatus(200);
        // The form must post nested seo[...] names, otherwise the controller drops them.
        $response->assertSee('name="seo[meta_title]"', false);
        $response->assertSee('name="seo[meta_description]"', false);
        // And it must round-trip the stored morph values back into the inputs.
        $response->assertSee('FAQ Meta Title Tersimpan', false);
        $response->assertSee('FAQ meta description tersimpan.', false);
    }

    public function test_public_page_renders_seo_meta_from_morph(): void
    {
        $page = Page::create([
            'user_id' => $this->user->id,
            'title' => 'Syarat Layanan',
            'slug' => 'syarat-layanan',
            'content' => '<p>Isi syarat layanan.</p>',
            'status' => 'published',
        ]);

        $page->seo()->updateOrCreate([], [
            'meta_title' => 'Judul SEO Halaman Unik',
            'meta_description' => 'Deskripsi SEO halaman unik.',
        ]);

        $response = $this->get(route('pages.show', $page->slug));

        $response->assertStatus(200);
        $response->assertSee('<title>Judul SEO Halaman Unik', false);
        $response->assertSee('Deskripsi SEO halaman unik.', false);
    }

    public function test_property_admin_form_exposes_nested_seo_inputs(): void
    {
        $this->authenticate();

        $property = Property::factory()->create(['slug' => 'seo-input-property']);

        $property->seo()->updateOrCreate([], [
            'meta_title' => 'Property Meta Title Tersimpan',
            'meta_description' => 'Property meta description tersimpan.',
        ]);

        $response = $this->get(route('admin.properties.edit', $property));

        $response->assertStatus(200);
        $response->assertSee('name="seo[meta_title]"', false);
        $response->assertSee('name="seo[meta_description]"', false);
        $response->assertSee('Property Meta Title Tersimpan', false);
        $response->assertSee('Property meta description tersimpan.', false);
    }

    public function test_property_page_prefers_morph_over_legacy_columns(): void
    {
        $property = Property::factory()->create([
            'slug' => 'morph-wins-property',
            'status' => 'published',
        ]);

        // Legacy flat columns still exist on the table but are no longer fillable
        // and must never win over the morph.
        $property->forceFill([
            'meta_title' => 'LEGACY-COLUMN-TITLE',
            'meta_description' => 'Legacy column description.',
        ])->save();

        $property->seo()->updateOrCreate([], [
            'meta_title' => 'MORPH-TITLE-WINS',
            'meta_description' => 'Morph description wins.',
        ]);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('MORPH-TITLE-WINS', false);
        $response->assertDontSee('LEGACY-COLUMN-TITLE', false);
    }

    public function test_properties_index_has_its_own_seo_title(): void
    {
        Property::factory()->create(['status' => 'published']);

        $response = $this->get(route('properties.public.index'));

        $response->assertStatus(200);
        $response->assertSee('Cari Apartemen &amp; Properti Sewa', false);
    }

    // ==================== System Pages (non-CMS routes) ====================

    public function test_admin_pages_index_lists_system_pages(): void
    {
        $this->authenticate();

        $response = $this->get(route('admin.pages.index'));

        $response->assertStatus(200);
        // syncRegistry() runs on index, so every registry label must be present.
        foreach (SystemPage::REGISTRY as $meta) {
            $response->assertSee($meta['label'], false);
        }

        $this->assertSame(count(SystemPage::REGISTRY), SystemPage::count());
    }

    public function test_system_page_seo_update_persists_to_morph(): void
    {
        $this->authenticate();

        SystemPage::syncRegistry();
        $systemPage = SystemPage::where('key', 'home')->firstOrFail();

        $response = $this->put(route('admin.pages.system-seo.update', $systemPage), [
            'seo' => [
                'meta_title' => 'Sewa Apartemen Murah Se-Jabodetabek',
                'meta_description' => 'Harga transparan, booking instan, tanpa perantara.',
                'index_status' => '1',
                'open_graph' => ['image' => 'og/home.jpg'],
            ],
        ]);

        $response->assertRedirect(route('admin.pages.index'));

        $systemPage->refresh();

        $this->assertNotNull($systemPage->seo);
        $this->assertSame('Sewa Apartemen Murah Se-Jabodetabek', $systemPage->seo->meta_title);
        $this->assertSame('Harga transparan, booking instan, tanpa perantara.', $systemPage->seo->meta_description);
        $this->assertSame(['image' => 'og/home.jpg'], $systemPage->seo->open_graph);
        // An untouched Twitter group must persist as null, not empty strings.
        $this->assertNull($systemPage->seo->twitter);
    }

    public function test_homepage_uses_system_page_override(): void
    {
        SystemPage::syncRegistry();
        SystemPage::where('key', 'home')->firstOrFail()->seo()->updateOrCreate([], [
            'meta_title' => 'JUDUL-HOMEPAGE-KUSTOM',
            'meta_description' => 'Deskripsi homepage kustom dari admin.',
            'index_status' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('JUDUL-HOMEPAGE-KUSTOM', false);
        $response->assertSee('Deskripsi homepage kustom dari admin.', false);
    }

    public function test_properties_index_uses_system_page_override(): void
    {
        Property::factory()->create(['status' => 'published']);

        SystemPage::syncRegistry();
        SystemPage::where('key', 'properties.index')->firstOrFail()->seo()->updateOrCreate([], [
            'meta_title' => 'JUDUL-LISTING-KUSTOM',
            'index_status' => true,
        ]);

        $response = $this->get(route('properties.public.index'));

        $response->assertStatus(200);
        $response->assertSee('JUDUL-LISTING-KUSTOM', false);
        $response->assertDontSee('Cari Apartemen &amp; Properti Sewa', false);
    }

    public function test_property_detail_uses_template_with_placeholders(): void
    {
        $property = Property::factory()->create([
            'name' => 'Skyhouse BSD',
            'city' => 'Tangerang Selatan',
            'slug' => 'template-placeholder-property',
            'status' => 'published',
            'unit_types' => ['studio'],
            'prices' => ['studio' => ['night_wd' => 150000]],
        ]);

        SystemPage::syncRegistry();
        SystemPage::where('key', 'properties.show')->firstOrFail()->seo()->updateOrCreate([], [
            'meta_title' => 'Sewa :name :city Harian — Mulai :price',
            'index_status' => true,
        ]);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('Sewa Skyhouse BSD Tangerang Selatan Harian — Mulai Rp 150.000', false);
    }

    public function test_property_own_seo_beats_the_template(): void
    {
        $property = Property::factory()->create([
            'name' => 'Treepark BSD',
            'slug' => 'own-seo-beats-template',
            'status' => 'published',
        ]);

        SystemPage::syncRegistry();
        SystemPage::where('key', 'properties.show')->firstOrFail()->seo()->updateOrCreate([], [
            'meta_title' => 'TEMPLATE-:name',
            'index_status' => true,
        ]);

        $property->seo()->updateOrCreate([], [
            'meta_title' => 'PER-LISTING-WINS',
            'index_status' => true,
        ]);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('PER-LISTING-WINS', false);
        $response->assertDontSee('TEMPLATE-Treepark BSD', false);
    }

    public function test_system_page_noindex_is_rendered(): void
    {
        SystemPage::syncRegistry();
        SystemPage::where('key', 'contact')->firstOrFail()->seo()->updateOrCreate([], [
            'meta_title' => 'Kontak Kami',
            'index_status' => false,
        ]);

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertSee('content="noindex, follow"', false);
    }

    public function test_system_page_open_graph_override_is_rendered(): void
    {
        SystemPage::syncRegistry();
        SystemPage::where('key', 'promotions')->firstOrFail()->seo()->updateOrCreate([], [
            'meta_title' => 'Promo Bulan Ini',
            'open_graph' => ['title' => 'OG-PROMO-TITLE', 'image' => 'og/promo.jpg'],
            'index_status' => true,
        ]);

        $response = $this->get(route('promotions'));

        $response->assertStatus(200);
        $response->assertSee('property="og:title" content="OG-PROMO-TITLE"', false);
        $response->assertSee('og/promo.jpg', false);
        // The <title> still uses meta_title, not the OG-only override.
        $response->assertSee('<title>Promo Bulan Ini', false);
    }

    public function test_system_page_seo_requires_admin(): void
    {
        SystemPage::syncRegistry();
        $systemPage = SystemPage::where('key', 'home')->firstOrFail();

        // Guest
        $this->get(route('admin.pages.system-seo.edit', $systemPage))->assertRedirect(route('login'));

        // Authenticated non-admin
        $this->actingAs($this->user)
            ->get(route('admin.pages.system-seo.edit', $systemPage))
            ->assertForbidden();
    }
}
