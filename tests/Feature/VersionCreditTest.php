<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins the "Powered by Artivo CMS vX.Y.Z" credit and the single canonical
 * version accessor: config('artivo.version') in config/artivo.php.
 *
 * Later features (admin update checker, Git rollback UI) read the version
 * through that accessor, so it is treated as a contract here.
 */
class VersionCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        return $user;
    }

    public function test_version_accessor_returns_expected_semver_string(): void
    {
        $version = config('artivo.version');

        $this->assertSame('1.0.0', $version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    public function test_product_identity_config_is_available(): void
    {
        $this->assertSame('Artivo CMS', config('artivo.product'));
        $this->assertSame('https://artivo.artupski.com', config('artivo.url'));
    }

    public function test_public_footer_renders_credit_with_version_and_outbound_url(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('footer.powered_by'), false);
        $response->assertSee('Artivo CMS', false);
        $response->assertSee('v'.config('artivo.version'), false);
        $response->assertSee('href="https://artivo.artupski.com"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_admin_layout_renders_credit_for_authenticated_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('footer.powered_by'), false);
        $response->assertSee('Artivo CMS', false);
        $response->assertSee('v'.config('artivo.version'), false);
        $response->assertSee('href="https://artivo.artupski.com"', false);
    }

    public function test_credit_link_opens_in_new_tab_safely(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<a\s[^>]*href="https:\/\/artivo\.artupski\.com"[^>]*target="_blank"[^>]*rel="noopener noreferrer"/',
            $html
        );
    }
}
