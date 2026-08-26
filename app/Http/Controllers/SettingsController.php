<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
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
            'site_name'         => 'required|string|max:255',
            'site_description'  => 'nullable|string',
            'site_logo'         => 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'site_favicon'      => 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg,ico|max:1024',
            'contact_email'     => 'nullable|email|max:255',
            'contact_phone'     => 'nullable|string|max:50',
            'contact_address'   => 'nullable|string',
            'whatsapp_default'  => 'nullable|string|max:50',
            'timezone'          => 'nullable|string|max:100',
            'locale'            => 'nullable|string|in:en,id',
            'currency'          => 'nullable|string|in:USD,IDR,EUR',
        ],
        'homepage' => [
            'hero_title'        => 'nullable|string|max:255',
            'hero_subtitle'     => 'nullable|string',
            'cta_title'         => 'nullable|string|max:255',
            'cta_text'          => 'nullable|string',
            'cta_button_label'  => 'nullable|string|max:255',
            'cta_button_url'    => 'nullable|url|max:500',
            'features_title'    => 'nullable|string|max:255',
            'features_subtitle' => 'nullable|string',
        ],
        'footer' => [
            'footer_about'      => 'nullable|string',
            'footer_copyright'  => 'nullable|string|max:255',
            'social_facebook'   => 'nullable|url',
            'social_twitter'    => 'nullable|url',
            'social_instagram'  => 'nullable|url',
            'social_linkedin'   => 'nullable|url',
            'social_youtube'    => 'nullable|url',
        ],
        'theme' => [
            'primary_color'     => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'header_layout'     => 'nullable|string|in:default,centered,minimal',
            'footer_layout'     => 'nullable|string|in:default,columns,minimal',
            'enable_dark_mode'  => 'nullable|boolean',
        ],
        'seo' => [
            'meta_description'      => 'nullable|string',
            'meta_keywords'         => 'nullable|string',
            'google_analytics'      => 'nullable|string',
            'facebook_pixel'        => 'nullable|string',
            'google_analytics_id'   => 'nullable|string|max:255',
            'google_tag_manager_id' => 'nullable|string|max:255|regex:/^GTM-[A-Z0-9]+$/i',
            'meta_pixel_id'         => 'nullable|string|max:255',
            'search_console_token'  => 'nullable|string|max:255',
            'microsoft_clarity_id'  => 'nullable|string|max:255',
            'google_maps_api_key'   => 'nullable|string|max:255',
        ],
        'integrations' => [
            'notification_webhook'        => 'nullable|url|max:500',
            'notification_webhook_secret' => 'nullable|string|max:255',
        ],
        'pricing' => [
            'weekend_days_mode'          => 'nullable|string|in:sat_sun,fri_sun,custom',
            'weekend_start_day'          => 'nullable|integer|min:0|max:6',
            'weekend_end_day'            => 'nullable|integer|min:0|max:6',
            'booking_display_mode'       => 'nullable|string|in:form_only,pricing_only,both',
            'booking_min_transit_hours'  => 'nullable|integer|min:1|max:24',
            'booking_checkin_default_time'  => 'nullable|string|max:10',
            'booking_checkout_default_time' => 'nullable|string|max:10',
        ],
        'mail' => [
            'mail_mailer'       => 'nullable|string|in:smtp,sendmail,log,array',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_encryption'   => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'nullable|email',
            'mail_from_name'    => 'nullable|string|max:255',
        ],
        'email_templates' => [
            'email_booking_confirmation_subject'  => 'nullable|string|max:255',
            'email_booking_confirmation_body'     => 'nullable|string',
            'email_booking_cancellation_subject'  => 'nullable|string|max:255',
            'email_booking_cancellation_body'     => 'nullable|string',
            'email_password_reset_subject'        => 'nullable|string|max:255',
            'email_password_reset_body'           => 'nullable|string',
            'email_welcome_subject'               => 'nullable|string|max:255',
            'email_welcome_body'                  => 'nullable|string',
        ],
        'captcha' => [
            'captcha_provider'             => 'nullable|string|in:none,recaptcha_v2,recaptcha_v3,hcaptcha,turnstile',
            'captcha_site_key'             => 'nullable|string|max:255',
            'captcha_secret_key'           => 'nullable|string|max:255',
            'captcha_recaptcha_min_score'  => 'nullable|numeric|min:0|max:1',
        ],
        'currency_api' => [
            'currency_api_url'    => 'nullable|url|max:500',
            'currency_api_key'    => 'nullable|string|max:255',
            'currency_target_list' => 'nullable|string|max:255',
        ],
    ];

    public function __construct(SettingsService $settingsService)
    {
        // BUG-007 FIX: only super-admins can access settings
        $this->middleware(['auth', 'admin']);
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings management page for a specific group.
     */
    public function index(string $group = 'general')
    {
        // Validate the group; fall back to general for unknown slugs
        $validGroups = array_keys($this->groupKeys);
        if (! in_array($group, $validGroups)) {
            $group = 'general';
        }

        $settings = [
            // General
            'site_name'          => $this->settingsService->get('site_name', config('app.name')),
            'site_description'   => $this->settingsService->get('site_description'),
            'site_logo'          => $this->settingsService->get('site_logo'),
            'site_favicon'       => $this->settingsService->get('site_favicon'),
            'contact_email'      => $this->settingsService->get('contact_email'),
            'contact_phone'      => $this->settingsService->get('contact_phone'),
            'contact_address'    => $this->settingsService->get('contact_address'),
            'whatsapp_default'   => $this->settingsService->get('whatsapp_default'),
            'timezone'           => $this->settingsService->get('timezone', 'UTC'),
            'locale'             => $this->settingsService->get('locale', 'en'),
            'currency'           => $this->settingsService->get('currency', 'IDR'),
            // Homepage
            'hero_title'         => $this->settingsService->get('hero_title'),
            'hero_subtitle'      => $this->settingsService->get('hero_subtitle'),
            'cta_title'          => $this->settingsService->get('cta_title'),
            'cta_text'           => $this->settingsService->get('cta_text'),
            'cta_button_label'   => $this->settingsService->get('cta_button_label'),
            'cta_button_url'     => $this->settingsService->get('cta_button_url'),
            'features_title'     => $this->settingsService->get('features_title'),
            'features_subtitle'  => $this->settingsService->get('features_subtitle'),
            // Footer
            'footer_about'       => $this->settingsService->get('footer_about'),
            'footer_copyright'   => $this->settingsService->get('footer_copyright'),
            'social_facebook'    => $this->settingsService->get('social_facebook'),
            'social_twitter'     => $this->settingsService->get('social_twitter'),
            'social_instagram'   => $this->settingsService->get('social_instagram'),
            'social_linkedin'    => $this->settingsService->get('social_linkedin'),
            'social_youtube'     => $this->settingsService->get('social_youtube'),
            // Theme
            'primary_color'      => $this->settingsService->get('primary_color', '#3b82f6'),
            'secondary_color'    => $this->settingsService->get('secondary_color', '#10b981'),
            'accent_color'       => $this->settingsService->get('accent_color', '#8b5cf6'),
            'header_layout'      => $this->settingsService->get('header_layout', 'default'),
            'footer_layout'      => $this->settingsService->get('footer_layout', 'default'),
            'enable_dark_mode'   => $this->settingsService->get('enable_dark_mode', false),
            // SEO
            'meta_description'      => $this->settingsService->get('meta_description'),
            'meta_keywords'         => $this->settingsService->get('meta_keywords'),
            'google_analytics'      => $this->settingsService->get('google_analytics'),
            'facebook_pixel'        => $this->settingsService->get('facebook_pixel'),
            'google_analytics_id'   => $this->settingsService->get('google_analytics_id'),
            'google_tag_manager_id' => $this->settingsService->get('google_tag_manager_id'),
            'meta_pixel_id'         => $this->settingsService->get('meta_pixel_id'),
            'search_console_token'  => $this->settingsService->get('search_console_token'),
            'microsoft_clarity_id'  => $this->settingsService->get('microsoft_clarity_id'),
            'google_maps_api_key'   => $this->settingsService->get('google_maps_api_key'),
            // Integrations
            'notification_webhook'        => $this->settingsService->get('notification_webhook'),
            'notification_webhook_secret' => $this->settingsService->get('notification_webhook_secret'),
            // Pricing / Booking
            'weekend_days_mode'             => $this->settingsService->get('weekend_days_mode', 'sat_sun'),
            'weekend_start_day'             => $this->settingsService->get('weekend_start_day', '5'),
            'weekend_end_day'               => $this->settingsService->get('weekend_end_day', '0'),
            'booking_display_mode'          => $this->settingsService->get('booking_display_mode', 'both'),
            'booking_min_transit_hours'     => $this->settingsService->get('booking_min_transit_hours', '3'),
            'booking_checkin_default_time'  => $this->settingsService->get('booking_checkin_default_time', '14:00'),
            'booking_checkout_default_time' => $this->settingsService->get('booking_checkout_default_time', '12:00'),
            // Mail
            'mail_mailer'       => $this->settingsService->get('mail_mailer', 'smtp'),
            'mail_host'         => $this->settingsService->get('mail_host'),
            'mail_port'         => $this->settingsService->get('mail_port', '587'),
            'mail_username'     => $this->settingsService->get('mail_username'),
            'mail_password'     => $this->settingsService->get('mail_password'),
            'mail_encryption'   => $this->settingsService->get('mail_encryption', 'tls'),
            'mail_from_address' => $this->settingsService->get('mail_from_address'),
            'mail_from_name'    => $this->settingsService->get('mail_from_name', config('app.name')),
            // Email Templates
            'email_booking_confirmation_subject' => $this->settingsService->get('email_booking_confirmation_subject'),
            'email_booking_confirmation_body'    => $this->settingsService->get('email_booking_confirmation_body'),
            'email_booking_cancellation_subject' => $this->settingsService->get('email_booking_cancellation_subject'),
            'email_booking_cancellation_body'    => $this->settingsService->get('email_booking_cancellation_body'),
            'email_password_reset_subject'       => $this->settingsService->get('email_password_reset_subject'),
            'email_password_reset_body'          => $this->settingsService->get('email_password_reset_body'),
            'email_welcome_subject'              => $this->settingsService->get('email_welcome_subject'),
            'email_welcome_body'                 => $this->settingsService->get('email_welcome_body'),
            // Captcha
            'captcha_provider'            => $this->settingsService->get('captcha_provider', 'none'),
            'captcha_site_key'            => $this->settingsService->get('captcha_site_key', ''),
            'captcha_secret_key'          => $this->settingsService->get('captcha_secret_key', ''),
            'captcha_recaptcha_min_score' => $this->settingsService->get('captcha_recaptcha_min_score', '0.5'),
            // Currency API
            'currency_api_url'    => $this->settingsService->get('currency_api_url', ''),
            'currency_api_key'    => $this->settingsService->get('currency_api_key', ''),
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
            $validator = Validator::make($request->all(), $rules);

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
                            'base_folder'   => 'Settings',
                            'sub_folders'   => [$slugField],
                            'name_prefix'   => 'Settings',
                            'name_category' => $slugField,
                        ]);
                        $data[$field] = $result['path'];
                    } elseif ($request->boolean('remove_' . $field)) {
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
                'group'     => $group,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
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

            $branch = $this->runGit('git rev-parse --abbrev-ref HEAD', $cwd);
            $currentCommit = $this->runGit('git rev-parse HEAD', $cwd);
            $currentMessage = $this->runGit('git log -1 --pretty=%s', $cwd);

            // Count commits behind origin/main (or origin/master)
            $remoteBranch = 'origin/main';
            $countBehind = (int) $this->runGit("git rev-list --count HEAD..{$remoteBranch}", $cwd);
            if ($countBehind === 0 && trim($this->runGit("git rev-parse --verify {$remoteBranch} 2>/dev/null", $cwd)) === '') {
                $remoteBranch = 'origin/master';
                $countBehind = (int) $this->runGit("git rev-list --count HEAD..{$remoteBranch}", $cwd);
            }

            // Upcoming commit list
            $logRaw = $this->runGit("git log HEAD..{$remoteBranch} --oneline", $cwd);
            $upcomingCommits = [];
            foreach (array_filter(explode("\n", trim($logRaw))) as $line) {
                $parts = explode(' ', $line, 2);
                $upcomingCommits[] = [
                    'hash'    => $parts[0] ?? '',
                    'message' => $parts[1] ?? '',
                ];
            }

            return response()->json([
                'branch'           => trim($branch),
                'current_commit'   => trim($currentCommit),
                'current_message'  => trim($currentMessage),
                'commits_behind'   => $countBehind,
                'upcoming_commits' => $upcomingCommits,
            ]);
        } catch (\Exception $e) {
            Log::error('gitStatus failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
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
            $output = $this->runGit('git pull origin main 2>&1', $cwd);

            return response()->json([
                'success' => true,
                'output'  => trim($output),
            ]);
        } catch (\Exception $e) {
            Log::error('gitPull failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /admin/settings/git-fetch
     * Runs `git fetch origin`. Returns JSON { success }.
     */
    public function gitFetch()
    {
        try {
            $cwd = base_path();
            $this->runGit('git fetch origin 2>&1', $cwd);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('gitFetch failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: run a git command in the given working directory.
     * Returns the stdout output as a string.
     *
     * @throws \RuntimeException on non-zero exit code
     */
    private function runGit(string $command, string $cwd): string
    {
        $output = '';
        $returnCode = 0;
        $oldDir = getcwd();

        try {
            chdir($cwd);
            exec($command, $lines, $returnCode);
            $output = implode("\n", $lines);
        } finally {
            chdir($oldDir);
        }

        if ($returnCode !== 0 && !str_contains($command, '2>/dev/null')) {
            // Non-fatal for status queries; fatal for pull/fetch
            if (str_contains($command, 'git pull') || str_contains($command, 'git fetch')) {
                throw new \RuntimeException("Git command failed (exit {$returnCode}): {$command}\nOutput: {$output}");
            }
        }

        return $output;
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
            Log::error('Clear cache failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }
}
