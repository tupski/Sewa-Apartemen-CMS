<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SlugSettingsController extends Controller
{
    // Public route slugs that are editable
    const SLUGS = [
        'slug_apartments'      => ['label_key' => 'slug_settings.apartments_label', 'default' => 'apartments',     'example' => '/apartments'],
        'slug_blog'            => ['label_key' => 'slug_settings.blog_label',       'default' => 'blog',           'example' => '/blog'],
        'slug_booking'         => ['label_key' => 'slug_settings.booking_label',    'default' => 'bookings',       'example' => '/bookings (POST)'],
        'slug_booking_success' => ['label_key' => 'slug_settings.booking_success_label', 'default' => 'bookings',  'example' => '/bookings/{token}/success'],
        'slug_booking_status'  => ['label_key' => 'slug_settings.booking_status_label',  'default' => 'booking/status', 'example' => '/booking/status/{token}'],
        'admin_prefix'         => ['label_key' => 'slug_settings.admin_prefix_label',    'default' => 'admin',      'example' => '/{admin_prefix}/login'],
    ];

    public function index()
    {
        $slugs = [];
        foreach (self::SLUGS as $key => $meta) {
            $slugs[$key] = array_merge($meta, [
                'label' => __($meta['label_key']),
                'value' => SettingsService::get($key, $meta['default']),
            ]);
        }
        return view('admin.slug-settings.index', compact('slugs'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::SLUGS as $key => $meta) {
            $rules[$key] = 'nullable|string|max:80|regex:/^[a-z0-9\-\/]+$/';
        }
        $data = $request->validate($rules);

        $oldAdminPrefix = SettingsService::get('admin_prefix', 'admin');

        foreach (self::SLUGS as $key => $meta) {
            $val = trim($data[$key] ?? '', '/') ?: $meta['default'];
            SettingsService::set($key, $val, 'slugs');
        }

        $newAdminPrefix = SettingsService::get('admin_prefix', 'admin');

        // Route cache must be cleared whenever a slug changes (routes are built
        // from these settings at boot). For admin_prefix we also clear config cache
        // so the admin panel prefix is re-resolved on the next request.
        try { \Artisan::call('route:clear'); } catch (\Throwable $e) {}
        if ($oldAdminPrefix !== $newAdminPrefix) {
            try { \Artisan::call('config:clear'); } catch (\Throwable $e) {}
        }

        return back()->with('success', __('slug_settings.saved'));
    }
}
