<?php

namespace App\Http\Controllers;

use App\Console\Commands\CheckForGitUpdates;
use App\Services\BackupService;
use App\Services\GitService;
use App\Services\PostUpdateActionService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;

class SettingsController extends Controller
{
    /**
     * SEC-13: Generic message returned to the client for git failures.
     * Full detail (paths, remote URLs) stays in the application log only.
     */
    private const GIT_GENERIC_ERROR = 'Git operation failed. Check the application log for details.';

    protected $settingsService;

    /**
     * Keys that belong to each settings group.
     */
    protected array $groupKeys = [
        'general' => [
            'site_name', 'site_description', 'site_logo', 'site_favicon',
            'contact_email', 'contact_phone', 'contact_address',
            'whatsapp_default', 'timezone', 'locale', 'currency',
        ],
        'homepage' => [
            'hero_title', 'hero_subtitle',
            'cta_title', 'cta_text', 'cta_button_label', 'cta_button_url',
            'features_title', 'features_subtitle',
        ],
        'footer' => [
            'footer_about', 'footer_copyright',
            'social_facebook', 'social_twitter', 'social_instagram',
            'social_linkedin', 'social_youtube',
        ],
        'theme' => [
            'primary_color', 'secondary_color', 'accent_color',
            'header_layout', 'footer_layout', 'enable_dark_mode',
        ],
        'seo' => [
            'meta_description', 'meta_keywords',
            'google_analytics', 'facebook_pixel',
            'google_analytics_id', 'google_tag_manager_id',
            'meta_pixel_id', 'search_console_token', 'microsoft_clarity_id',
            'google_maps_api_key',
        ],
        'integrations' => [
            'notification_webhook', 'notification_webhook_secret',
        ],
        'pricing' => [
            'weekend_days_mode', 'weekend_start_day', 'weekend_end_day',
            'booking_display_mode',
            'booking_min_transit_hours', 'booking_checkin_default_time',
            'booking_checkout_default_time',
        ],
        'mail' => [
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name',
        ],
        'email_templates' => [
            'email_booking_confirmation_subject', 'email_booking_confirmation_body',
            'email_booking_cancellation_subject', 'email_booking_cancellation_body',
            'email_password_reset_subject', 'email_password_reset_body',
            'email_welcome_subject', 'email_welcome_body',
        ],
        'captcha' => [
            'captcha_provider', 'captcha_site_key', 'captcha_secret_key',
            'captcha_recaptcha_min_score',
        ],
        'currency_api' => [
            'currency_api_url', 'currency_api_key', 'currency_target_list',
        ],
    ];

    /**
     * Validation rules per group.
     */
    protected array $groupRules = [
        'general' => [
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string',
            'site_logo' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'site_favicon' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg,ico|max:1024',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_address' => 'nullable|string',
            'whatsapp_default' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:100',
            'locale' => 'nullable|string|in:en,id',
            'currency' => 'nullable|string|in:USD,IDR,EUR',
        ],
        'homepage' => [
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string',
            'cta_title' => 'nullable|string|max:255',
            'cta_text' => 'nullable|string',
            'cta_button_label' => 'nullable|string|max:255',
            'cta_button_url' => 'nullable|url|max:500',
            'features_title' => 'nullable|string|max:255',
            'features_subtitle' => 'nullable|string',
        ],
        'footer' => [
            'footer_about' => 'nullable|string',
            'footer_copyright' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|url',
            'social_twitter' => 'nullable|url',
            'social_instagram' => 'nullable|url',
            'social_linkedin' => 'nullable|url',
            'social_youtube' => 'nullable|url',
        ],
        'theme' => [
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'header_layout' => 'nullable|string|in:default,centered,minimal',
            'footer_layout' => 'nullable|string|in:default,columns,minimal',
            'enable_dark_mode' => 'nullable|boolean',
        ],
        // NOTE: every rule in this group is expressed as an ARRAY, not a
        // pipe-delimited string. Laravel splits string rules on "|", which would
        // tear a regex containing alternation (e.g. "(?:G|GT|AW)") into invalid
        // fragments and raise "preg_match(): No ending delimiter".
        'seo' => [
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            // Legacy fields: retained for backwards compatibility but NOT rendered
            // into any <script> by AnalyticsService, so no format constraint is
            // imposed beyond a length bound.
            'google_analytics' => ['nullable', 'string', 'max:255'],
            'facebook_pixel' => ['nullable', 'string', 'max:255'],
            // Every rule below is a hard allowlist, not cosmetic validation: each
            // value is interpolated into inline <script>/<iframe> output by
            // AnalyticsService, so the character set must stay free of quotes,
            // angle brackets, and whitespace (AGENTS.md §15).
            //
            // This field feeds BOTH gtag/js?id= and gtag('config', '…') in
            // AnalyticsService::ga4Script(), so it must accept every ID family
            // that gtag.js itself accepts:
            //   G-…  GA4 measurement ID   (e.g. G-ABC1234567)
            //   GT-… Google tag ID        (e.g. GT-ABC1234)
            //   AW-… Google Ads conversion ID
            // Legacy UA-… belongs in the separate "Google Analytics (Legacy)"
            // field, so it is deliberately NOT accepted here. Google publishes no
            // fixed length, hence the deliberately wide 4..15 window.
            'google_analytics_id' => ['nullable', 'string', 'max:255', 'regex:/^(?:G|GT|AW)-[A-Z0-9]{4,15}$/i'],
            // GTM container ID. This field feeds gtm.js?id=, which ONLY resolves
            // GTM- containers — a G-/GT-/AW- ID here silently loads nothing, so
            // the prefix stays locked to GTM-. Bounded to 4..12 to cover classic
            // (GTM-ABC1234) and newer longer containers while keeping the value
            // length-sane for a string interpolated into inline JS.
            'google_tag_manager_id' => ['nullable', 'string', 'max:255', 'regex:/^GTM-[A-Z0-9]{4,12}$/i'],
            // Meta (Facebook) Pixel ID — a purely numeric 10..16 digit identifier.
            'meta_pixel_id' => ['nullable', 'string', 'max:255', 'regex:/^[0-9]{10,16}$/'],
            // Microsoft Clarity project ID — lowercase base36-ish token.
            'microsoft_clarity_id' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]{4,20}$/i'],
            // Search Console verification token — URL-safe base64 alphabet only.
            'search_console_token' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]{10,100}$/'],
            // Google Maps browser API key — always "AIza" + 35 URL-safe chars.
            'google_maps_api_key' => ['nullable', 'string', 'max:255', 'regex:/^AIza[A-Za-z0-9_-]{35}$/'],
        ],
        'integrations' => [
            'notification_webhook' => 'nullable|url|max:500',
            'notification_webhook_secret' => 'nullable|string|max:255',
        ],
        'pricing' => [
            'weekend_days_mode' => 'nullable|string|in:sat_sun,fri_sun,custom',
            'weekend_start_day' => 'nullable|integer|min:0|max:6',
            'weekend_end_day' => 'nullable|integer|min:0|max:6',
            'booking_display_mode' => 'nullable|string|in:form_only,pricing_only,both',
            'booking_min_transit_hours' => 'nullable|integer|min:1|max:24',
            'booking_checkin_default_time' => 'nullable|string|max:10',
            'booking_checkout_default_time' => 'nullable|string|max:10',
        ],
        'mail' => [
            'mail_mailer' => 'nullable|string|in:smtp,sendmail,log,array',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string|max:255',
        ],
        'email_templates' => [
            'email_booking_confirmation_subject' => 'nullable|string|max:255',
            'email_booking_confirmation_body' => 'nullable|string',
            'email_booking_cancellation_subject' => 'nullable|string|max:255',
            'email_booking_cancellation_body' => 'nullable|string',
            'email_password_reset_subject' => 'nullable|string|max:255',
            'email_password_reset_body' => 'nullable|string',
            'email_welcome_subject' => 'nullable|string|max:255',
            'email_welcome_body' => 'nullable|string',
        ],
        'captcha' => [
            'captcha_provider' => 'nullable|string|in:none,recaptcha_v2,recaptcha_v3,hcaptcha,turnstile',
            'captcha_site_key' => 'nullable|string|max:255',
            'captcha_secret_key' => 'nullable|string|max:255',
            'captcha_recaptcha_min_score' => 'nullable|numeric|min:0|max:1',
        ],
        'currency_api' => [
            'currency_api_url' => 'nullable|url|max:500',
            'currency_api_key' => 'nullable|string|max:255',
            'currency_target_list' => 'nullable|string|max:255',
        ],
    ];

    /**
     * Groups that only render a read-only / action panel and therefore have no
     * persisted keys in $groupKeys. They are valid for index() but not update().
     */
    protected array $viewOnlyGroups = [
        'version_control',
    ];

    /**
     * Custom validation messages per group.
     *
     * Without these, a failing `regex` rule renders the raw translation key
     * `validation.regex` in the admin UI, because this project ships JSON
     * translation files only (lang/en.json, lang/id.json) and has no
     * lang/{locale}/validation.php with the framework's default messages.
     *
     * Keyed by "field.rule" exactly like FormRequest::messages().
     *
     * @return array<string, array<string, string>>
     */
    protected function groupMessages(): array
    {
        return [
            'theme' => [
                'primary_color.regex' => __('settings.validation_hex_color'),
                'secondary_color.regex' => __('settings.validation_hex_color'),
                'accent_color.regex' => __('settings.validation_hex_color'),
            ],
            'seo' => [
                'google_analytics_id.regex' => __('settings.validation_ga4_id'),
                'google_tag_manager_id.regex' => __('settings.validation_gtm_id'),
                'meta_pixel_id.regex' => __('settings.validation_meta_pixel_id'),
                'microsoft_clarity_id.regex' => __('settings.validation_clarity_id'),
                'search_console_token.regex' => __('settings.validation_search_console_token'),
                'google_maps_api_key.regex' => __('settings.validation_maps_api_key'),
            ],
        ];
    }

    /**
     * Human-readable attribute names per group, so any rule this controller does
     * not supply an explicit message for (e.g. `max`) still reads sensibly
     * instead of exposing the raw snake_case setting key.
     *
     * @return array<string, array<string, string>>
     */
    protected function groupAttributes(): array
    {
        return [
            'seo' => [
                'meta_description' => __('settings.attr_meta_description'),
                'meta_keywords' => __('settings.attr_meta_keywords'),
                'google_analytics' => __('settings.attr_google_analytics_legacy'),
                'facebook_pixel' => __('settings.attr_facebook_pixel'),
                'google_analytics_id' => __('settings.attr_google_analytics_id'),
                'google_tag_manager_id' => __('settings.attr_google_tag_manager_id'),
                'meta_pixel_id' => __('settings.attr_meta_pixel_id'),
                'search_console_token' => __('settings.attr_search_console_token'),
                'microsoft_clarity_id' => __('settings.attr_microsoft_clarity_id'),
                'google_maps_api_key' => __('settings.attr_google_maps_api_key'),
            ],
        ];
    }

    public function __construct(
        SettingsService $settingsService,
        protected PostUpdateActionService $postUpdateActions,
        protected GitService $gitService,
        protected BackupService $backupService,
    ) {
        // BUG-007 FIX: only super-admins can access settings
        $this->middleware(['auth', 'admin']);
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings management page for a specific group.
     */
    public function index(string $group = 'general')
    {
        // Validate the group; fall back to general for unknown slugs.
        // View-only groups (e.g. version_control) have no persisted keys but are
        // still valid pages, so they must be whitelisted here as well.
        $validGroups = array_merge(array_keys($this->groupKeys), $this->viewOnlyGroups);
        if (! in_array($group, $validGroups)) {
            $group = 'general';
        }

        $settings = [
            // General
            'site_name' => $this->settingsService->get('site_name', config('app.name')),
            'site_description' => $this->settingsService->get('site_description'),
            'site_logo' => $this->settingsService->get('site_logo'),
            'site_favicon' => $this->settingsService->get('site_favicon'),
            'contact_email' => $this->settingsService->get('contact_email'),
            'contact_phone' => $this->settingsService->get('contact_phone'),
            'contact_address' => $this->settingsService->get('contact_address'),
            'whatsapp_default' => $this->settingsService->get('whatsapp_default'),
            'timezone' => $this->settingsService->get('timezone', 'UTC'),
            'locale' => $this->settingsService->get('locale', 'en'),
            'currency' => $this->settingsService->get('currency', 'IDR'),
            // Homepage
            'hero_title' => $this->settingsService->get('hero_title'),
            'hero_subtitle' => $this->settingsService->get('hero_subtitle'),
            'cta_title' => $this->settingsService->get('cta_title'),
            'cta_text' => $this->settingsService->get('cta_text'),
            'cta_button_label' => $this->settingsService->get('cta_button_label'),
            'cta_button_url' => $this->settingsService->get('cta_button_url'),
            'features_title' => $this->settingsService->get('features_title'),
            'features_subtitle' => $this->settingsService->get('features_subtitle'),
            // Footer
            'footer_about' => $this->settingsService->get('footer_about'),
            'footer_copyright' => $this->settingsService->get('footer_copyright'),
            'social_facebook' => $this->settingsService->get('social_facebook'),
            'social_twitter' => $this->settingsService->get('social_twitter'),
            'social_instagram' => $this->settingsService->get('social_instagram'),
            'social_linkedin' => $this->settingsService->get('social_linkedin'),
            'social_youtube' => $this->settingsService->get('social_youtube'),
            // Theme
            'primary_color' => $this->settingsService->get('primary_color', '#3b82f6'),
            'secondary_color' => $this->settingsService->get('secondary_color', '#10b981'),
            'accent_color' => $this->settingsService->get('accent_color', '#8b5cf6'),
            'header_layout' => $this->settingsService->get('header_layout', 'default'),
            'footer_layout' => $this->settingsService->get('footer_layout', 'default'),
            'enable_dark_mode' => $this->settingsService->get('enable_dark_mode', false),
            // SEO
            'meta_description' => $this->settingsService->get('meta_description'),
            'meta_keywords' => $this->settingsService->get('meta_keywords'),
            'google_analytics' => $this->settingsService->get('google_analytics'),
            'facebook_pixel' => $this->settingsService->get('facebook_pixel'),
            'google_analytics_id' => $this->settingsService->get('google_analytics_id'),
            'google_tag_manager_id' => $this->settingsService->get('google_tag_manager_id'),
            'meta_pixel_id' => $this->settingsService->get('meta_pixel_id'),
            'search_console_token' => $this->settingsService->get('search_console_token'),
            'microsoft_clarity_id' => $this->settingsService->get('microsoft_clarity_id'),
            'google_maps_api_key' => $this->settingsService->get('google_maps_api_key'),
            // Integrations
            'notification_webhook' => $this->settingsService->get('notification_webhook'),
            'notification_webhook_secret' => $this->settingsService->get('notification_webhook_secret'),
            // Pricing / Booking
            'weekend_days_mode' => $this->settingsService->get('weekend_days_mode', 'sat_sun'),
            'weekend_start_day' => $this->settingsService->get('weekend_start_day', '5'),
            'weekend_end_day' => $this->settingsService->get('weekend_end_day', '0'),
            'booking_display_mode' => $this->settingsService->get('booking_display_mode', 'both'),
            'booking_min_transit_hours' => $this->settingsService->get('booking_min_transit_hours', '3'),
            'booking_checkin_default_time' => $this->settingsService->get('booking_checkin_default_time', '14:00'),
            'booking_checkout_default_time' => $this->settingsService->get('booking_checkout_default_time', '12:00'),
            // Mail
            'mail_mailer' => $this->settingsService->get('mail_mailer', 'smtp'),
            'mail_host' => $this->settingsService->get('mail_host'),
            'mail_port' => $this->settingsService->get('mail_port', '587'),
            'mail_username' => $this->settingsService->get('mail_username'),
            'mail_password' => $this->settingsService->get('mail_password'),
            'mail_encryption' => $this->settingsService->get('mail_encryption', 'tls'),
            'mail_from_address' => $this->settingsService->get('mail_from_address'),
            'mail_from_name' => $this->settingsService->get('mail_from_name', config('app.name')),
            // Email Templates
            'email_booking_confirmation_subject' => $this->settingsService->get('email_booking_confirmation_subject'),
            'email_booking_confirmation_body' => $this->settingsService->get('email_booking_confirmation_body'),
            'email_booking_cancellation_subject' => $this->settingsService->get('email_booking_cancellation_subject'),
            'email_booking_cancellation_body' => $this->settingsService->get('email_booking_cancellation_body'),
            'email_password_reset_subject' => $this->settingsService->get('email_password_reset_subject'),
            'email_password_reset_body' => $this->settingsService->get('email_password_reset_body'),
            'email_welcome_subject' => $this->settingsService->get('email_welcome_subject'),
            'email_welcome_body' => $this->settingsService->get('email_welcome_body'),
            // Captcha
            'captcha_provider' => $this->settingsService->get('captcha_provider', 'none'),
            'captcha_site_key' => $this->settingsService->get('captcha_site_key', ''),
            'captcha_secret_key' => $this->settingsService->get('captcha_secret_key', ''),
            'captcha_recaptcha_min_score' => $this->settingsService->get('captcha_recaptcha_min_score', '0.5'),
            // Currency API
            'currency_api_url' => $this->settingsService->get('currency_api_url', ''),
            'currency_api_key' => $this->settingsService->get('currency_api_key', ''),
            'currency_target_list' => $this->settingsService->get('currency_target_list', 'USD,SGD,MYR,EUR,AUD,GBP,JPY'),
        ];

        return view('admin.settings.index', compact('settings', 'group'));
    }

    /**
     * Update settings for a specific group.
     */
    public function update(Request $request, string $group)
    {
        $validGroups = array_keys($this->groupKeys);
        if (! in_array($group, $validGroups)) {
            abort(404, "Unknown settings group: {$group}");
        }

        try {
            $rules = $this->groupRules[$group] ?? [];
            $validator = Validator::make(
                $request->all(),
                $rules,
                $this->groupMessages()[$group] ?? [],
                $this->groupAttributes()[$group] ?? [],
            );

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('active_group', $group);
            }

            $data = $validator->validated();

            // Handle file uploads only for the general group
            if ($group === 'general') {
                foreach (['site_logo', 'site_favicon'] as $field) {
                    if ($request->hasFile($field) && $request->file($field)->isValid()) {
                        // Delete the old file if it exists
                        $existing = $this->settingsService->get($field);
                        if ($existing && Storage::disk('public')->exists($existing)) {
                            Storage::disk('public')->delete($existing);
                        }
                        $slugField = str_replace('site_', '', $field);
                        $result = upload_file($request->file($field), [
                            'base_folder' => 'Settings',
                            'sub_folders' => [$slugField],
                            'name_prefix' => 'Settings',
                            'name_category' => $slugField,
                        ]);
                        $data[$field] = $result['path'];
                    } elseif ($request->boolean('remove_'.$field)) {
                        // User requested removal of the stored image.
                        // Delete the physical file from the public disk (guarded), then null the value.
                        $existing = $this->settingsService->get($field);
                        if ($existing && Storage::disk('public')->exists($existing)) {
                            Storage::disk('public')->delete($existing);
                        }
                        $data[$field] = '';
                    } else {
                        // No new file uploaded and no removal requested: keep the stored value
                        unset($data[$field]);
                    }
                }
            }

            // Persist each setting with its determined group
            foreach ($data as $key => $value) {
                $this->settingsService->set($key, $value, $this->determineSettingGroup($key));
            }

            return redirect()
                ->route('admin.settings.index', ['group' => $group])
                ->with('success', 'Pengaturan berhasil disimpan.');

        } catch (\Exception $e) {
            Log::error('Failed to update settings', [
                'group' => $group,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // SEC-13: detail lengkap hanya di log, klien menerima pesan generik.
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengaturan. Periksa log aplikasi untuk detail.');
        }
    }

    /**
     * Determine the database group for a given setting key.
     */
    protected function determineSettingGroup(string $key): string
    {
        foreach ($this->groupKeys as $group => $keys) {
            if (in_array($key, $keys)) {
                return $group;
            }
        }

        // Legacy fallback for keys not in the map
        if (in_array($key, ['site_name', 'site_description', 'site_logo', 'site_favicon',
            'contact_email', 'contact_phone', 'contact_address', 'whatsapp_default',
            'timezone', 'locale', 'currency'])) {
            return 'general';
        } elseif (in_array($key, ['captcha_provider', 'captcha_site_key', 'captcha_secret_key', 'captcha_recaptcha_min_score'])) {
            return 'captcha';
        } elseif (in_array($key, ['currency_api_url', 'currency_api_key', 'currency_target_list'])) {
            return 'currency_api';
        } elseif (in_array($key, ['primary_color', 'secondary_color', 'accent_color', 'header_layout', 'footer_layout', 'enable_dark_mode'])) {
            return 'theme';
        } elseif (in_array($key, ['meta_description', 'meta_keywords', 'google_analytics', 'facebook_pixel'])) {
            return 'seo';
        } elseif (in_array($key, ['google_analytics_id', 'google_tag_manager_id', 'meta_pixel_id', 'search_console_token', 'microsoft_clarity_id'])) {
            return 'integrations';
        } elseif (str_starts_with($key, 'notification_webhook')) {
            return 'integrations';
        } elseif (str_starts_with($key, 'hero_') || str_starts_with($key, 'cta_') || str_starts_with($key, 'features_')) {
            return 'homepage';
        } elseif (str_starts_with($key, 'mail_')) {
            return 'mail';
        } elseif (str_starts_with($key, 'email_')) {
            return 'email_templates';
        }

        return 'general';
    }

    /**
     * GET /admin/settings/git-status
     * Returns current branch, commit hash, commit message, commits_behind, upcoming_commits.
     */
    public function gitStatus()
    {
        try {
            $cwd = base_path();

            $branch = $this->runGit(['rev-parse', '--abbrev-ref', 'HEAD'], $cwd);
            $currentCommit = $this->runGit(['rev-parse', 'HEAD'], $cwd);
            $currentMessage = $this->runGit(['log', '-1', '--pretty=%s'], $cwd);

            // Count commits behind origin/main (or origin/master).
            // $remoteBranch is always passed as a discrete process argument — never
            // interpolated into a shell string (SEC-03).
            $remoteBranch = 'origin/main';
            $countBehind = (int) $this->runGit(['rev-list', '--count', 'HEAD..'.$remoteBranch], $cwd);
            if ($countBehind === 0 && $this->runGit(['rev-parse', '--verify', $remoteBranch], $cwd) === '') {
                $remoteBranch = 'origin/master';
                $countBehind = (int) $this->runGit(['rev-list', '--count', 'HEAD..'.$remoteBranch], $cwd);
            }

            // Upcoming commit list
            $logRaw = $this->runGit(['log', 'HEAD..'.$remoteBranch, '--oneline'], $cwd);
            $upcomingCommits = [];
            foreach (array_filter(explode("\n", trim($logRaw))) as $line) {
                $parts = explode(' ', $line, 2);
                $upcomingCommits[] = [
                    'hash' => $parts[0] ?? '',
                    'message' => $parts[1] ?? '',
                ];
            }

            return response()->json([
                'branch' => trim($branch),
                'current_commit' => trim($currentCommit),
                'current_message' => trim($currentMessage),
                'commits_behind' => $countBehind,
                'upcoming_commits' => $upcomingCommits,
                // Which post-update actions the pending commits would require.
                'needed_actions' => $countBehind > 0
                    ? $this->postUpdateActions->detect($this->changedFiles('HEAD', $remoteBranch, $cwd))
                    : [],
            ]);
        } catch (\Exception $e) {
            Log::error('gitStatus failed: '.$e->getMessage());

            return response()->json(['error' => $this->gitErrorMessage($e)], 500);
        }
    }

    /**
     * POST /admin/settings/git-pull
     * Runs `git pull origin main` (falls back to master). Returns JSON { success, output }.
     */
    public function gitPull()
    {
        try {
            $cwd = base_path();
            $before = trim($this->runGit(['rev-parse', 'HEAD'], $cwd));
            $output = $this->runGit(['pull', 'origin', 'main'], $cwd, true, true);
            $after = trim($this->runGit(['rev-parse', 'HEAD'], $cwd));

            return response()->json([
                'success' => true,
                'output' => trim($output),
                // Derived from the files the pull actually changed, so the UI can
                // show ONLY the post-update buttons that are required.
                'needed_actions' => $before === $after
                    ? []
                    : $this->postUpdateActions->detect($this->changedFiles($before, $after, $cwd)),
            ]);
        } catch (\Exception $e) {
            Log::error('gitPull failed: '.$e->getMessage());

            return response()->json(['success' => false, 'error' => $this->gitErrorMessage($e)], 500);
        }
    }

    /**
     * POST /admin/settings/post-update/{action}
     * Runs one allowlisted post-update command set and returns its combined output.
     *
     * SEC-03: $action is only a KEY. The argv arrays are hardcoded in
     * PostUpdateActionService::commands() and executed via Symfony Process with an
     * argument array, so no request data can reach a shell. Unknown keys => 422.
     */
    public function gitPostUpdate(Request $request, string $action)
    {
        if (! in_array($action, PostUpdateActionService::allowedKeys(), true)) {
            return response()->json([
                'success' => false,
                'error' => __('Unknown post-update action.'),
            ], 422);
        }

        try {
            $output = $this->postUpdateActions->run($action);
            log_activity('post_update', 'Ran post-update action: '.$action);

            return response()->json(['success' => true, 'output' => $output]);
        } catch (\Throwable $e) {
            Log::error('gitPostUpdate failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                // Command output only — never .env values or secrets.
                'error' => __('The post-update command failed. Check the server log for details.'),
            ], 500);
        }
    }

    /**
     * Changed file paths between two git revisions (relative to the repo root).
     *
     * @return array<int, string>
     */
    private function changedFiles(string $from, string $to, string $cwd): array
    {
        $raw = $this->runGit(['diff', '--name-only', $from.'..'.$to], $cwd);

        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }

    /**
     * POST /admin/settings/git-fetch
     * Runs `git fetch origin`. Returns JSON { success }.
     */
    public function gitFetch()
    {
        try {
            $cwd = base_path();
            $this->runGit(['fetch', 'origin'], $cwd, true, true);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('gitFetch failed: '.$e->getMessage());

            return response()->json(['success' => false, 'error' => $this->gitErrorMessage($e)], 500);
        }
    }

    /**
     * Helper: run a git command in the given working directory.
     *
     * SEC-03: uses Symfony Process with array arguments, so nothing is passed
     * through a shell and no argument can be interpolated into a command string.
     * The working directory is supplied as the process cwd instead of chdir(),
     * which avoids mutating global process state.
     *
     * @param  array<int, string>  $args  git arguments, without the leading "git"
     * @param  bool  $throwOnFailure  throw when git exits non-zero (pull/fetch)
     * @param  bool  $includeStderr  append stderr to the returned output
     * @return string trimmed stdout (plus stderr when requested)
     *
     * @throws \RuntimeException on non-zero exit code when $throwOnFailure is true
     */
    private function runGit(array $args, string $cwd, bool $throwOnFailure = false, bool $includeStderr = false): string
    {
        // Fail fast with an actionable message when the deploy directory is not a
        // git checkout — the common cause of the perpetual "gagal fetch" on the
        // server. Checked before spawning a process so the error is unambiguous.
        if (! is_dir(rtrim($cwd, '/\\').DIRECTORY_SEPARATOR.'.git')) {
            throw new \RuntimeException('fatal: not a git repository: the .git directory was not found in the deploy path.');
        }

        // `git -C <path>` makes git resolve the repository from an explicit path
        // regardless of the launching service account or mount — more reliable
        // than the process cwd alone (which is still passed as a belt-and-suspenders
        // fallback). GIT_TERMINAL_PROMPT=0 makes remote operations fail fast instead
        // of hanging when credentials are unavailable. Args stay an array (SEC-03).
        $process = new Process(
            array_merge(['git', '-C', $cwd], $args),
            $cwd,
            ['GIT_TERMINAL_PROMPT' => '0']
        );
        $process->setTimeout(120);
        $process->run();

        $output = trim($process->getOutput());

        if ($includeStderr) {
            $stderr = trim($process->getErrorOutput());
            if ($stderr !== '') {
                $output = $output === '' ? $stderr : $output."\n".$stderr;
            }
        }

        if (! $process->isSuccessful() && $throwOnFailure) {
            throw new \RuntimeException(sprintf(
                "Git command failed (exit %s): git %s\nOutput: %s\nError: %s",
                $process->getExitCode(),
                implode(' ', $args),
                trim($process->getOutput()),
                trim($process->getErrorOutput())
            ));
        }

        // Non-fatal for status queries: return whatever stdout was produced.
        return $output;
    }

    /**
     * SEC-13: Map a raw git failure (exception message / stderr) to a short,
     * safe-to-expose category message plus the first sanitized stderr line.
     * The full, unredacted detail is logged by the caller — never returned here.
     */
    private function gitErrorMessage(\Throwable $e): string
    {
        $raw = $e->getMessage();
        $lower = strtolower($raw);

        if (str_contains($lower, 'not a git repository')) {
            $category = __('Not a git repository — the .git directory was not found on the server.');
        } elseif (str_contains($lower, 'dubious ownership') || str_contains($lower, 'safe.directory')) {
            $category = __('Git reported dubious repository ownership. Add the repo to git safe.directory on the server.');
        } elseif (str_contains($lower, 'authentication failed')
            || str_contains($lower, 'could not read username')
            || str_contains($lower, 'permission denied')
            || str_contains($lower, 'access denied')) {
            $category = __('Git authentication failed. Check the deploy key or credentials on the server.');
        } elseif (str_contains($lower, 'could not resolve host')
            || str_contains($lower, 'failed to connect')
            || str_contains($lower, 'unable to access')
            || str_contains($lower, 'network is unreachable')
            || str_contains($lower, 'connection timed out')) {
            $category = __('Could not reach the git remote. Check the server network/DNS connection.');
        } else {
            $category = __(self::GIT_GENERIC_ERROR);
        }

        $detail = $this->sanitizeGitStderr($raw);

        return $detail === '' ? $category : $category.' ('.$detail.')';
    }

    /**
     * SEC-13: Return the first meaningful stderr line with absolute paths,
     * remote URLs, and credentials redacted so nothing sensitive leaks to the client.
     */
    private function sanitizeGitStderr(string $raw): string
    {
        $line = '';
        foreach (preg_split('/\r?\n/', $raw) as $candidate) {
            $candidate = trim($candidate);
            // Skip the wrapper lines added by runGit() so we surface the real git message.
            if ($candidate === ''
                || str_starts_with($candidate, 'Git command failed')
                || str_starts_with($candidate, 'Output:')) {
                continue;
            }
            $line = preg_replace('/^Error:\s*/i', '', $candidate);
            if ($line !== '') {
                break;
            }
        }

        if ($line === '') {
            return '';
        }

        // Redact remote URLs (scheme://.., git@host:..), then absolute filesystem paths.
        $line = preg_replace('#\b[a-z][a-z0-9+.-]*://\S+#i', '[remote]', $line);
        $line = preg_replace('#\bgit@\S+#i', '[remote]', $line);
        $line = preg_replace('#(?:[A-Za-z]:\\\\|/)[^\s\'"()]+#', '[path]', $line);

        return trim($line);
    }

    /**
     * GET /admin/settings/git-remote-info
     * Returns remote origin URL (credential-redacted), current branch, upstream
     * tracking branch, and detached-HEAD state.
     */
    public function gitRemoteInfo()
    {
        try {
            return response()->json([
                'success' => true,
                'remote' => $this->gitService->getRemoteInfo(base_path()),
            ]);
        } catch (\Throwable $e) {
            Log::error('gitRemoteInfo failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $this->gitErrorMessage($e),
            ], 500);
        }
    }

    /**
     * GET /admin/settings/git-commit-history
     * Returns the commit history table rows. Supports "show more" via ?limit
     * (a multiple of the SHOW_MORE_INCREMENT) or ?skip.
     */
    public function gitCommitHistory(Request $request)
    {
        try {
            $limit = (int) $request->query('limit', GitService::COMMIT_DISPLAY_LIMIT);
            $skip = (int) $request->query('skip', 0);

            // Clamp: limit must be positive, skip non-negative. Both are bounded
            // so an attacker cannot request a pathological result set.
            $limit = max(1, min($limit, 200));
            $skip = max(0, $skip);

            $commits = $this->gitService->getCommitHistory(base_path(), $limit, $skip);

            return response()->json([
                'success' => true,
                'commits' => $commits,
                'display_limit' => GitService::COMMIT_DISPLAY_LIMIT,
                'increment' => GitService::SHOW_MORE_INCREMENT,
            ]);
        } catch (\Throwable $e) {
            Log::error('gitCommitHistory failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $this->gitErrorMessage($e),
            ], 500);
        }
    }

    /**
     * POST /admin/settings/git-rollback
     * Roll back to a specific commit via `git checkout <commit>` (detached HEAD).
     *
     * SECURITY: the SHA arrives from the client and is attacker-controlled. It is
     * validated server-side against /^[0-9a-f]{7,40}$/ AND resolved against the
     * real repository (cat-file -t must return 'commit') before any checkout.
     * The checkout runs via Symfony Process with an argument array — never a
     * shell string — so nothing the client sends can reach a shell.
     */
    public function gitRollback(Request $request)
    {
        // This is a state-changing, destructive action — super-admins only.
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        // SEC-xx: $sha arrives from the client and is attacker-controlled.
        // Reject anything not matching /^[0-9a-f]{7,40}$/ with 422 before any
        // git command runs. Use # as the regex delimiter so forward slashes in
        // the subject never confuse the pattern parser.
        $validator = Validator::make($request->all(), [
            'sha' => ['required', 'string', 'regex:#^[0-9a-f]{7,40}$#'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => __('git.sha_invalid_format'),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $cwd = base_path();

            // Server-side validation + existence check. The regex above is a
            // first gate; validateCommitSha() also verifies the SHA resolves to
            // a real commit object in this repo.
            $check = $this->gitService->validateCommitSha($data['sha'], $cwd);
            if (! $check['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => $check['error'],
                ], 422);
            }

            // Never allow rolling back to the current HEAD — you cannot roll back
            // to where you already are.
            $currentHead = trim($this->runGit(['rev-parse', 'HEAD'], $cwd));
            if ($currentHead !== '' && $currentHead === $check['full_hash']) {
                return response()->json([
                    'success' => false,
                    'error' => __('git.rollback_current_head'),
                ], 422);
            }

            $result = $this->gitService->rollback($check['full_hash'], $cwd);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'steps' => $result['steps'],
                    'error' => __('git.rollback_failed'),
                ], 409);
            }

            log_activity('git_rollback', 'Rolled back to commit '.$check['full_hash']);

            return response()->json([
                'success' => true,
                'steps' => $result['steps'],
                'message' => __('git.rollback_success'),
            ]);
        } catch (\Throwable $e) {
            Log::error('gitRollback failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $this->gitErrorMessage($e),
            ], 500);
        }
    }

    /**
     * POST /admin/settings/git-return-to-branch
     * Return to the default branch tip (main/master) from detached HEAD.
     */
    public function gitReturnToBranch()
    {
        try {
            $result = $this->gitService->returnToBranchTip(base_path());

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 409);
            }

            log_activity('git_return_to_branch', 'Returned to branch '.$result['branch']);

            return response()->json([
                'success' => true,
                'output' => $result['output'],
                'branch' => $result['branch'],
                'message' => __('git.return_success', ['branch' => $result['branch']]),
            ]);
        } catch (\Throwable $e) {
            Log::error('gitReturnToBranch failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $this->gitErrorMessage($e),
            ], 500);
        }
    }

    /**
     * POST /admin/settings/git-backup-database
     * Produces a complete `.sql` dump via the existing BackupService and stores
     * it under storage/app/private, returning a download link + filename.
     */
    public function gitBackupDatabase()
    {
        try {
            $result = $this->backupService->dumpSql();

            log_activity('git_backup_database', 'Created SQL backup '.$result['filename']);

            return response()->json([
                'success' => true,
                'filename' => $result['filename'],
                'path' => $result['path'],
                'message' => __('git.backup_db_success', ['filename' => $result['filename']]),
            ]);
        } catch (\Throwable $e) {
            Log::error('gitBackupDatabase failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /admin/settings/git-backup-download/{filename}
     * Streams a previously created SQL backup for download.
     */
    public function gitBackupDownload(string $filename)
    {
        // Filename arrives from a URL parameter — strictly validate it to prevent
        // path traversal (only "rollback-backup-*.sql" files under private storage).
        if (! preg_match('/^rollback-backup-[A-Za-z0-9_.-]+\.sql$/', $filename)) {
            abort(404);
        }

        $path = storage_path('app/private/'.$filename);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * POST /admin/settings/git-check-updates
     * Trigger an on-demand update check: calls GitService directly (so it can be
     * properly mocked in tests) and caches the result.
     *
     * AGENTS.md §14: no blocking git/network call on PAGE RENDER — this endpoint is
     * only called on an explicit user action (POST button), not on every page load.
     */
    public function gitCheckUpdates()
    {
        try {
            $result = $this->gitService->checkForUpdates(base_path());

            // Persist using the same cache key the scheduler writes, so the header
            // badge reflects the on-demand check immediately on next page load.
            Cache::forever(
                CheckForGitUpdates::CACHE_KEY,
                $result
            );

            return response()->json([
                'success' => true,
                'result' => $result,
                'message' => $result['available']
                    ? __('git.check_updates_available', ['count' => $result['commits_behind']])
                    : __('git.check_updates_up_to_date'),
            ]);
        } catch (\Throwable $e) {
            Log::error('gitCheckUpdates failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => self::GIT_GENERIC_ERROR,
            ], 500);
        }
    }

    /**
     * Clear application caches (cache, views, config, routes).
     * Called via POST /admin/clear-cache — returns JSON.
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Clear cache failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: '.$e->getMessage(),
            ], 500);
        }
    }
}
