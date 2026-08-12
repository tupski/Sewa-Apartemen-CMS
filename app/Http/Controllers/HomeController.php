<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Property;
use App\Models\Unit;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Render the public homepage.
     */
    public function index(Request $request)
    {
        $siteName = SettingsService::get('site_name', config('app.name', 'Kakarama Room'));
        $tagline = SettingsService::get('site_tagline', '');
        $description = SettingsService::get('site_description', '');

        $primaryColor = SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = SettingsService::get('secondary_color', '#10b981');
        $accentColor = SettingsService::get('accent_color', '#f59e0b');

        $properties = Property::published()
            ->featured()
            ->with(['featuredImage', 'units' => fn ($q) => $q->available()])
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Fall back to the latest published properties when none are featured yet
        if ($properties->isEmpty()) {
            $properties = Property::published()
                ->with(['featuredImage', 'units' => fn ($q) => $q->available()])
                ->orderBy('order')
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get();
        }

        $posts = Post::published()
            ->with('category')
            ->latest('published_at')
            ->take(3)
            ->get();

        $stats = [
            'properties' => Property::published()->count(),
            'units' => Unit::where('status', 'available')->count(),
            'cities' => Property::published()->distinct()->count('city'),
        ];

        $seo = SeoService::metaTags(
            $tagline ? "{$siteName} — {$tagline}" : $siteName,
            $description,
            url('/'),
        );

        return view('home', compact(
            'siteName',
            'tagline',
            'description',
            'primaryColor',
            'secondaryColor',
            'accentColor',
            'properties',
            'posts',
            'stats',
            'seo',
        ));
    }
}
