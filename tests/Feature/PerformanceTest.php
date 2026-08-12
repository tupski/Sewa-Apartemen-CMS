<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(User $user): User
    {
        $role = \App\Models\Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_dashboard_caches_stats(): void
    {
$admin = $this->makeAdmin(User::factory()->create());
        $this->actingAs($admin);

        $this->get(route('dashboard'))->assertStatus(200);
        $this->assertTrue(Cache::has('dashboard_stats'));

        $first = Cache::get('dashboard_stats');
        $this->get(route('dashboard'))->assertStatus(200);
        $second = Cache::get('dashboard_stats');

        $this->assertSame($first['totalProperties'], $second['totalProperties']);
    }

    public function test_sitemap_caching(): void
    {
        $this->get(route('sitemap'))->assertStatus(200);
        $this->assertTrue(Cache::has('sitemap.xml'));

        $first = Cache::get('sitemap.xml');
        $this->get(route('sitemap'))->assertStatus(200);
        $second = Cache::get('sitemap.xml');

        $this->assertSame($first, $second);
    }

    public function test_settings_caching(): void
    {
        \App\Services\SettingsService::clearCache();
        \App\Models\Setting::create(['key' => 'site_name', 'value' => 'Test Site', 'type' => 'string']);

        $value = \App\Services\SettingsService::get('site_name');
        $this->assertEquals('Test Site', $value);
        $this->assertTrue(Cache::has('settings'));
    }

    public function test_blog_sidebar_is_cached(): void
    {
        $this->get(route('blog.index'))->assertStatus(200);
        $this->assertTrue(Cache::has('blog_sidebar'));
    }
}
