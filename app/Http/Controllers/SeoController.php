<?php

namespace App\Http\Controllers;

use App\Services\RobotsService;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Serve the dynamic robots.txt.
     */
    public function robots(RobotsService $robotsService): Response
    {
        return response($robotsService->generate(), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Serve the dynamic sitemap.xml.
     */
    public function sitemap(SitemapService $sitemapService): Response
    {
        $xml = $sitemapService->generate();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('X-Robots-Tag', 'noindex');
    }
}
