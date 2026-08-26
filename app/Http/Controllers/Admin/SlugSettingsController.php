<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SlugSettingsController extends Controller
{
    // Public route slugs that are editable
    const SLUGS = [
        'slug_apartments'      => ['label' => 'Halaman Apartemen',    'default' => 'apartments',      'example' => '/apartments'],
        'slug_blog'            => ['label' => 'Halaman Blog',         'default' => 'blog',            'example' => '/blog'],
        'slug_booking_success' => ['label' => 'Booking Success',      'default' => 'bookings',        'example' => '/bookings/{token}/success'],
        'slug_booking_status'  => ['label' => 'Booking Status',       'default' => 'booking/status',  'example' => '/booking/status/{token}'],
        'admin_prefix'         => ['label' => 'Path Login Admin',     'default' => 'admin',           'example' => '/{admin_prefix}/login'],
    ];

    public function index()
    {
        $slugs = [];
        foreach (self::SLUGS as $key => $meta) {
            $slugs[$key] = array_merge($meta, [
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

        foreach (self::SLUGS as $key => $meta) {
            $val = trim($data[$key] ?? '', '/') ?: $meta['default'];
            SettingsService::set($key, $val, 'slugs');
        }

        // If admin_prefix changed, we must clear route cache
        try { \Artisan::call('route:clear'); } catch (\Throwable $e) {}

        return back()->with('success', 'Slug berhasil disimpan. Pastikan jalankan php artisan route:clear jika ada perubahan path admin.');
    }
}
