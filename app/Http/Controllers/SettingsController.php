<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->middleware('auth');
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings management page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get all settings grouped by group
        $settings = [
            'general' => [
                'site_name' => $this->settingsService->get('site_name', config('app.name')),
                'site_description' => $this->settingsService->get('site_description'),
                'site_logo' => $this->settingsService->get('site_logo'),
                'site_favicon' => $this->settingsService->get('site_favicon'),
                'contact_email' => $this->settingsService->get('contact_email'),
                'contact_phone' => $this->settingsService->get('contact_phone'),
                'contact_address' => $this->settingsService->get('contact_address'),
            ],
            'footer' => [
                'footer_about' => $this->settingsService->get('footer_about'),
                'footer_copyright' => $this->settingsService->get('footer_copyright'),
                'social_facebook' => $this->settingsService->get('social_facebook'),
                'social_twitter' => $this->settingsService->get('social_twitter'),
                'social_instagram' => $this->settingsService->get('social_instagram'),
                'social_linkedin' => $this->settingsService->get('social_linkedin'),
                'social_youtube' => $this->settingsService->get('social_youtube'),
            ],
            'theme' => [
                'primary_color' => $this->settingsService->get('primary_color', '#3b82f6'),
                'secondary_color' => $this->settingsService->get('secondary_color', '#10b981'),
                'header_layout' => $this->settingsService->get('header_layout', 'default'),
                'footer_layout' => $this->settingsService->get('footer_layout', 'default'),
                'enable_dark_mode' => $this->settingsService->get('enable_dark_mode', false),
            ],
            'seo' => [
                'meta_description' => $this->settingsService->get('meta_description'),
                'meta_keywords' => $this->settingsService->get('meta_keywords'),
                'google_analytics' => $this->settingsService->get('google_analytics'),
                'facebook_pixel' => $this->settingsService->get('facebook_pixel'),
            ],
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
            // Validate the request
            $validator = Validator::make($request->all(), [
                'site_name' => 'nullable|string|max:255',
                'site_description' => 'nullable|string',
                'site_logo' => 'nullable|string|max:255',
                'site_favicon' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email',
                'contact_phone' => 'nullable|string|max:50',
                'contact_address' => 'nullable|string',
                'footer_about' => 'nullable|string',
                'footer_copyright' => 'nullable|string|max:255',
                'social_facebook' => 'nullable|url',
                'social_twitter' => 'nullable|url',
                'social_instagram' => 'nullable|url',
                'social_linkedin' => 'nullable|url',
                'social_youtube' => 'nullable|url',
                'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'header_layout' => 'nullable|string|in:default,centered,minimal',
                'footer_layout' => 'nullable|string|in:default,minimal,extended',
                'enable_dark_mode' => 'nullable|boolean',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
                'google_analytics' => 'nullable|string',
                'facebook_pixel' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Update each setting
            foreach ($request->except('_token', '_method') as $key => $value) {
                // Determine the group based on key prefix or specific keys
                $group = $this->determineSettingGroup($key);

                $this->settingsService->set($key, $value, $group);
            }

            return redirect()
                ->route('admin.settings.index')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
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
        } elseif (in_array($key, ['primary_color', 'secondary_color', 'header_layout', 'footer_layout', 'enable_dark_mode'])) {
            return 'theme';
        } elseif (in_array($key, ['meta_description', 'meta_keywords', 'google_analytics', 'facebook_pixel'])) {
            return 'seo';
        }
        return 'general';
    }
}
