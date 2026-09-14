<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Flux\Flux;
use FluxPro\FluxProServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FluxSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ],
            ['model_type' => User::class]
        );

        return $user;
    }

    // ==================== Phase 0 — Flux Setup ====================

    public function test_flux_pro_package_is_resolvable(): void
    {
        $this->assertTrue(Flux::pro());
        $this->assertTrue(class_exists(FluxProServiceProvider::class));
    }

    public function test_admin_can_view_flux_smoke_page_with_flux_components(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.flux.setup'));

        $response->assertOk();
        $response->assertSee('Phase 0 — Flux Setup Smoke Test', false);
        $response->assertSee('flux-setup', false);
        // Livewire component is rendered (root wire:id snapshot present)
        $response->assertSee('wire:id', false);
        // Flux admin CSS (Tailwind v4 pipeline) is linked
        $response->assertSee('assets/admin.css', false);
    }

    public function test_flux_smoke_page_requires_authentication(): void
    {
        $this->get(route('admin.flux.setup'))->assertRedirect(route('login'));
    }

    public function test_flux_smoke_page_forbidden_for_non_admin(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('admin.flux.setup'))->assertForbidden();
    }

    public function test_flux_scripts_route_serves_pro_bundle(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.flux.setup'));
        $response->assertOk();

        // Pro bundle ter-link dari halaman smoke dengan hash versi dari manifest flux-pro
        $hash = (string) json_decode(
            file_get_contents(base_path('packages/flux-pro/dist/manifest.json')),
            true
        )['/flux.js'];
        $file = config('app.debug') ? 'flux.js' : 'flux.min.js';
        $response->assertSee('/flux/'.$file.'?id='.$hash, false);

        $fluxJs = $this->actingAs($user)->get('/flux/flux.min.js');
        $fluxJs->assertOk();
        // response()->file() = StreamedResponse: baca via streamedContent()
        $this->assertNotEmpty($fluxJs->streamedContent());
    }

    public function test_public_homepage_stays_on_existing_stack(): void
    {
        // PHPUnit menjalankan semua test dalam SATU proses; statik internal Livewire
        // ($forceAssetInjection) bocor dari test smoke sebelumnya. flushState()
        // meniru siklus request fresh (fpm/artisan serve = proses baru per request).
        Livewire::flushState();

        $response = $this->get('/');

        $response->assertOk();
        $html = $response->getContent();

        // Existing public stack untouched: Vite app bundle, no Livewire payload
        $this->assertStringContainsString('build/assets/app-', $html);
        $this->assertStringNotContainsString('livewire.min.js', $html);
        $this->assertStringNotContainsString('Livewire Styles', $html);
    }
}
