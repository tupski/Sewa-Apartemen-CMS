<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        // BUG-007 FIX: Tambahkan middleware 'admin' agar hanya super-admin yang bisa
        // mengakses settings. Sebelumnya hanya 'auth' yang dicek — user non-admin
        // berpotensi mengubah konfigurasi situs, logo, webhook, dan API key.
        $this->middleware(['auth', 'admin']);
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings management page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
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
            // Homepage (hero / CTA / features)
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
            // Integrations
            'google_analytics_id' => $this->settingsService->get('google_analytics_id'),
            'google_tag_manager_id' => $this->settingsService->get('google_tag_manager_id'),
            'meta_pixel_id' => $this->settingsService->get('meta_pixel_id'),
            'search_console_token' => $this->settingsService->get('search_console_token'),
            'microsoft_clarity_id' => $this->settingsService->get('microsoft_clarity_id'),
            'google_maps_api_key' => $this->settingsService->get('google_maps_api_key'),
            // Webhook (owner notifications)
            'notification_webhook' => $this->settingsService->get('notification_webhook'),
            'notification_webhook_secret' => $this->settingsService->get('notification_webhook_secret'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // Required fields
                'site_name' => 'required|string|max:255',
                'contact_email' => 'required|email',
                'contact_phone' => 'required|string|max:50',
                // Optional fields
                'site_description' => 'nullable|string',
                'site_logo' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
                'site_favicon' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg,ico|max:1024',
                'contact_address' => 'nullable|string',
                'whatsapp_default' => 'nullable|string|max:50',
                'timezone' => 'nullable|string|max:100',
                'locale' => 'nullable|string|in:en,id',
                'currency' => 'nullable|string|in:USD,IDR,EUR',
                'hero_title' => 'nullable|string|max:255',
                'hero_subtitle' => 'nullable|string',
                'cta_title' => 'nullable|string|max:255',
                'cta_text' => 'nullable|string',
                'cta_button_label' => 'nullable|string|max:255',
                'cta_button_url' => 'nullable|url|max:500',
                'features_title' => 'nullable|string|max:255',
                'features_subtitle' => 'nullable|string',
                'footer_about' => 'nullable|string',
                'footer_copyright' => 'nullable|string|max:255',
                'social_facebook' => 'nullable|url',
                'social_twitter' => 'nullable|url',
                'social_instagram' => 'nullable|url',
                'social_linkedin' => 'nullable|url',
                'social_youtube' => 'nullable|url',
                'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'header_layout' => 'nullable|string|in:default,centered,minimal',
                'footer_layout' => 'nullable|string|in:default,minimal,extended',
                'enable_dark_mode' => 'nullable|boolean',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
                'google_analytics' => 'nullable|string',
                'facebook_pixel' => 'nullable|string',
                'google_analytics_id' => 'nullable|string|max:255',
                'google_tag_manager_id' => 'nullable|string|max:255',
                'meta_pixel_id' => 'nullable|string|max:255',
                'search_console_token' => 'nullable|string|max:255',
                'microsoft_clarity_id' => 'nullable|string|max:255',
                'google_maps_api_key' => 'nullable|string|max:255',
                'notification_webhook' => 'nullable|url|max:500',
                'notification_webhook_secret' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Periksa kembali isian yang ditandai merah.');
            }

            // Normalize checkbox so it is always persisted (false when unchecked)
            $request->merge(['enable_dark_mode' => $request->boolean('enable_dark_mode')]);

            $data = $request->except('_token', '_method');

            // Handle file uploads
            foreach (['site_logo' => 'settings/logos', 'site_favicon' => 'settings/favicons'] as $field => $dir) {
                if ($request->hasFile($field)) {
                    $newPath = $request->file($field)->store($dir, 'public');

                    // Remove the previous file, if any
                    $oldPath = $this->settingsService->get($field);
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    $data[$field] = $newPath;
                } else {
                    // No new file uploaded: keep the stored value
                    unset($data[$field]);
                }
            }

            // Persist each setting
            foreach ($data as $key => $value) {
                $this->settingsService->set($key, $value, $this->determineSettingGroup($key));
            }

            return redirect()
                ->route('admin.settings.index')
                ->with('success', 'Pengaturan berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Failed to update settings', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Determine the setting group based on key.
     *
     * @param string $key
     * @return string
     */
    private function determineSettingGroup($key)
    {
        if (str_starts_with($key, 'footer_') || str_starts_with($key, 'social_')) {
            return 'footer';
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
        }

        return 'general';
    }
}
