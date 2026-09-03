<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Property;
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
        // Site Description is repurposed as the site tagline (used in the header
        // <title> and as the hero fallback). Fall back to the legacy site_tagline
        // key if a description has not been set yet.
        $description = SettingsService::get('site_description', '');
        $tagline = $description ?: SettingsService::get('site_tagline', '');

        $primaryColor = SettingsService::get('primary_color', '#3b82f6');
        $secondaryColor = SettingsService::get('secondary_color', '#10b981');
        $accentColor = SettingsService::get('accent_color', '#f59e0b');

        // Hero / CTA / features copy (editable from admin settings; empty => blade fallback)
        $heroTitle = SettingsService::get('hero_title', '');
        $heroSubtitle = SettingsService::get('hero_subtitle', '');
        $ctaTitle = SettingsService::get('cta_title', '');
        $ctaText = SettingsService::get('cta_text', '');
        $ctaButtonLabel = SettingsService::get('cta_button_label', '');
        $ctaButtonUrl = SettingsService::get('cta_button_url', '');
        $featuresTitle = SettingsService::get('features_title', '');
        $featuresSubtitle = SettingsService::get('features_subtitle', '');

        $properties = Property::published()
            ->featured()
            ->with(['featuredImage', 'photos.media', 'amenities'])
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Fall back to the latest published properties when none are featured yet
        if ($properties->isEmpty()) {
            $properties = Property::published()
                ->with(['featuredImage', 'photos.media', 'amenities'])
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
            'units' => Property::published()->get()->sum(fn ($p) => count($p->unit_types ?? [])),
            'cities' => Property::published()->distinct()->count('city'),
        ];

        // Homepage title format ({Site Name} - {Tagline}) is applied centrally
        // by SeoService::title() via homepage detection, so we pass the base
        // site name here and let the service append the tagline. An admin
        // override from admin Pages → System Pages (`home`) wins when present.
        $seo = SeoService::forSystemPage(
            'home',
            $siteName,
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
            'heroTitle',
            'heroSubtitle',
            'ctaTitle',
            'ctaText',
            'ctaButtonLabel',
            'ctaButtonUrl',
            'featuresTitle',
            'featuresSubtitle',
            'properties',
            'posts',
            'stats',
            'seo',
        ));
    }
}
