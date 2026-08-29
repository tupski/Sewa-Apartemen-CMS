<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the admin Settings > SEO tab validation contract.
 *
 * Two defects are pinned here:
 *  1. Analytics/verification IDs must accept real-world values.
 *  2. A failing rule must render a human-readable message, never the raw
 *     translation key `validation.regex` (the project ships JSON lang files
 *     only, so there is no lang/{locale}/validation.php fallback).
 */
class SeoSettingsValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($this->user);

        SettingsService::clearCache();
    }

    /**
     * Post the SEO group with only the given overrides filled in.
     *
     * @param  array<string, string>  $overrides
     */
    private function postSeo(array $overrides): TestResponse
    {
        return $this->from(route('admin.settings.index', ['group' => 'seo']))
            ->post(route('admin.settings.update', ['group' => 'seo']), $overrides);
    }

    // ==================== GTM container ID (the reported bug) ====================

    /**
     * @return array<string, array{0: string}>
     */
    public static function validGtmIdProvider(): array
    {
        return [
            'classic 5-char container' => ['GTM-ABC12'],
            'classic 7-char container' => ['GTM-ABC1234'],
            'newer 8-char container' => ['GTM-ABCD1234'],
            'digits only' => ['GTM-1234567'],
            'lowercase accepted (case-insensitive rule)' => ['gtm-abc1234'],
        ];
    }

    #[DataProvider('validGtmIdProvider')]
    public function test_valid_gtm_container_id_saves_successfully(string $gtmId): void
    {
        $response = $this->postSeo(['google_tag_manager_id' => $gtmId]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.index', ['group' => 'seo']));

        SettingsService::clearCache();
        $this->assertSame($gtmId, SettingsService::get('google_tag_manager_id'));
    }

    public function test_invalid_gtm_container_id_is_rejected_with_readable_message(): void
    {
        $response = $this->postSeo(['google_tag_manager_id' => 'G-ABC1234567']);

        $response->assertSessionHasErrors('google_tag_manager_id');

        $message = session('errors')->first('google_tag_manager_id');

        $this->assertStringNotContainsString('validation.regex', $message);
        $this->assertStringNotContainsString('settings.validation_', $message);
        $this->assertStringContainsString('GTM-', $message);
    }

    public function test_gtm_container_id_rejects_script_injection_payload(): void
    {
        $response = $this->postSeo([
            'google_tag_manager_id' => "GTM-ABC1234'});alert(1);//",
        ]);

        $response->assertSessionHasErrors('google_tag_manager_id');

        SettingsService::clearCache();
        $this->assertEmpty(SettingsService::get('google_tag_manager_id'));
    }

    // ==================== Google tag ID families ====================

    /**
     * gtag.js resolves G- (GA4), GT- (Google tag) and AW- (Google Ads) IDs, and
     * AnalyticsService::ga4Script() feeds this field straight into gtag/js?id=,
     * so all three must be accepted.
     *
     * @return array<string, array{0: string}>
     */
    public static function validGoogleTagIdProvider(): array
    {
        return [
            'GA4 measurement ID' => ['G-ABC1234567'],
            'Google tag ID' => ['GT-ABC1234'],
            'Google Ads conversion ID' => ['AW-123456789'],
            'lowercase accepted' => ['g-abc1234567'],
        ];
    }

    #[DataProvider('validGoogleTagIdProvider')]
    public function test_valid_google_tag_id_saves_successfully(string $tagId): void
    {
        $response = $this->postSeo(['google_analytics_id' => $tagId]);

        $response->assertSessionHasNoErrors();

        SettingsService::clearCache();
        $this->assertSame($tagId, SettingsService::get('google_analytics_id'));
    }

    // ==================== Every other SEO tab field ====================

    /**
     * One realistic value per field on the SEO tab. Posting them together must
     * persist every one of them without a single validation error.
     *
     * @return array<string, string>
     */
    private static function validSeoPayload(): array
    {
        return [
            'meta_description' => 'Sewa apartemen harian dan transit dengan harga transparan.',
            'meta_keywords' => 'sewa apartemen, apartemen harian, transit',
            'google_analytics' => 'UA-123456-1',
            'facebook_pixel' => '1234567890',
            'google_analytics_id' => 'G-ABC1234567',
            'google_tag_manager_id' => 'GTM-ABC1234',
            'meta_pixel_id' => '1234567890123456',
            'microsoft_clarity_id' => 'abc123xyz',
            'search_console_token' => 'google-site-verification-token_1',
            'google_maps_api_key' => 'AIza'.str_repeat('a', 35),
        ];
    }

    public function test_all_seo_tab_fields_save_with_realistic_values(): void
    {
        $payload = self::validSeoPayload();

        $response = $this->postSeo($payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.index', ['group' => 'seo']));

        SettingsService::clearCache();
        foreach ($payload as $key => $expected) {
            $this->assertSame($expected, SettingsService::get($key), "Setting [{$key}] was not persisted.");
        }
    }

    /**
     * Each constrained field, paired with a value that must be rejected.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidSeoFieldProvider(): array
    {
        return [
            'ga4 id without G- prefix' => ['google_analytics_id', 'UA-123456-1'],
            'ga4 id with quote injection' => ['google_analytics_id', "G-ABC123');alert(1)//"],
            'gtm id with wrong prefix' => ['google_tag_manager_id', 'GTX-ABC1234'],
            'meta pixel id non numeric' => ['meta_pixel_id', 'pixel-1234567890'],
            'meta pixel id too short' => ['meta_pixel_id', '12345'],
            'clarity id with angle bracket' => ['microsoft_clarity_id', '<script>abc'],
            'search console token too short' => ['search_console_token', 'short'],
            'search console token with quote' => ['search_console_token', 'token"onload="alert(1)'],
            'maps key wrong prefix' => ['google_maps_api_key', 'NOTAKEY'.str_repeat('a', 32)],
            'maps key wrong length' => ['google_maps_api_key', 'AIzaTooShort'],
        ];
    }

    #[DataProvider('invalidSeoFieldProvider')]
    public function test_invalid_seo_field_is_rejected_with_readable_message(string $field, string $value): void
    {
        $response = $this->postSeo([$field => $value]);

        $response->assertSessionHasErrors($field);

        $message = session('errors')->first($field);

        // The whole point of the fix: no raw translation keys leak to the admin.
        $this->assertStringNotContainsString('validation.', $message);
        $this->assertStringNotContainsString('settings.', $message);
        $this->assertNotSame('', trim($message));

        SettingsService::clearCache();
        $this->assertEmpty(SettingsService::get($field), "Rejected value for [{$field}] must not be persisted.");
    }

    // ==================== Messages are translated in both locales ====================

    public function test_regex_message_is_translated_in_indonesian(): void
    {
        $this->withSession(['locale' => 'id']);

        $response = $this->postSeo(['google_tag_manager_id' => 'nope']);

        $response->assertSessionHasErrors('google_tag_manager_id');

        $message = session('errors')->first('google_tag_manager_id');

        $this->assertStringNotContainsString('validation.regex', $message);
        $this->assertStringNotContainsString('settings.validation_', $message);
        $this->assertStringContainsString('Masukkan', $message);
    }

    // ==================== Empty values remain allowed ====================

    public function test_blank_seo_fields_are_allowed(): void
    {
        $response = $this->postSeo([
            'google_analytics_id' => '',
            'google_tag_manager_id' => '',
            'meta_pixel_id' => '',
            'microsoft_clarity_id' => '',
            'search_console_token' => '',
            'google_maps_api_key' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.index', ['group' => 'seo']));
    }
}
