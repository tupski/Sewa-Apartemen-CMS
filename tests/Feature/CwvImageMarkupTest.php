<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CWV Phase A: image markup correctness.
 *
 * Verifies (without touching the storage pipeline):
 *  - intrinsic width/height attributes rendered from media metadata
 *  - LCP image eager + fetchpriority="high", secondary images lazy
 *  - exactly one LCP preload per property detail page, matching the img URL
 *  - no preload / no fabricated dimensions when metadata is missing
 *  - Lucide pinned (no @latest) and deferred
 */
class CwvImageMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected function makeMedia(array $overrides = []): Media
    {
        return Media::create(array_merge([
            'disk' => 'public',
            'directory' => 'properties/cwv',
            'filename' => 'photo-'.uniqid().'.jpg',
            'original_filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'type' => 'image',
            'width' => 1600,
            'height' => 1200,
        ], $overrides));
    }

    protected function attachPhoto(Property $property, Media $media, int $sortOrder): PropertyPhoto
    {
        return PropertyPhoto::create([
            'property_id' => $property->id,
            'media_id' => $media->id,
            'category' => 'Interior',
            'sort_order' => $sortOrder,
        ]);
    }

    // ==================== Property detail ====================

    public function test_property_detail_lcp_image_is_eager_high_priority_with_dimensions(): void
    {
        $property = Property::factory()->create();
        $first = $this->makeMedia(['width' => 1600, 'height' => 1200]);
        $second = $this->makeMedia(['width' => 960, 'height' => 1280]);
        $this->attachPhoto($property, $first, 1);
        $this->attachPhoto($property, $second, 2);

        $html = $this->get(route('properties.public.show', $property->slug))
            ->assertOk()
            ->getContent();

        // First gallery image: eager + high priority + intrinsic dimensions.
        $this->assertStringContainsString(
            'width="1600" height="1200"',
            $html,
            'LCP image must carry real intrinsic dimensions.'
        );
        $this->assertSame(
            1,
            substr_count($html, 'loading="eager" fetchpriority="high"'),
            'Only the primary LCP image may be eager + high priority.'
        );
        // Secondary gallery images remain lazy, with their own real dimensions.
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('width="960" height="1280"', $html);
    }

    public function test_property_detail_has_exactly_one_lcp_preload_matching_img_url(): void
    {
        $property = Property::factory()->create();
        $first = $this->makeMedia(['filename' => 'lcp-shot.jpg', 'width' => 1600, 'height' => 1200]);
        $second = $this->makeMedia(['filename' => 'other-shot.jpg', 'width' => 960, 'height' => 1280]);
        $this->attachPhoto($property, $first, 1);
        $this->attachPhoto($property, $second, 2);

        $html = $this->get(route('properties.public.show', $property->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'rel="preload" as="image"'),
            'Exactly one LCP image preload is expected.'
        );

        $expectedUrl = Storage::disk('public')->url($first->directory.'/'.$first->filename);
        // Preload must point at the exact URL the <img> renders (media url
        // pipeline); without variants it is a plain href + fetchpriority.
        $this->assertStringContainsString('rel="preload" as="image"', $html);
        $this->assertStringContainsString('href="'.e($expectedUrl).'"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringNotContainsString('imagesrcset', $html, 'No variants generated → plain href preload.');
        $this->assertStringContainsString('src="'.e($expectedUrl).'"', $html);
        // Never preload the secondary gallery photos.
        $secondaryUrl = Storage::disk('public')->url($second->directory.'/'.$second->filename);
        $this->assertStringNotContainsString('href="'.e($secondaryUrl).'" fetchpriority', $html);
    }

    public function test_property_detail_skips_preload_and_dimensions_without_metadata(): void
    {
        $property = Property::factory()->create();
        $noDims = $this->makeMedia(['width' => null, 'height' => null]);
        $this->attachPhoto($property, $noDims, 1);

        $html = $this->get(route('properties.public.show', $property->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('rel="preload" as="image"', $html);
        // No fabricated fallback dimensions anywhere.
        $this->assertStringNotContainsString('width="300" height="300"', $html);
        // Still eager + high priority (LCP prioritisation does not depend on metadata).
        $this->assertSame(1, substr_count($html, 'loading="eager" fetchpriority="high"'));
    }

    // ==================== Homepage ====================

    public function test_homepage_first_property_card_is_eager_and_rest_lazy(): void
    {
        $eager = $this->makeMedia(['width' => 1200, 'height' => 900]);
        $lazy = $this->makeMedia(['width' => 800, 'height' => 600]);
        // Homepage orders by `order` asc — make the LCP candidate deterministic.
        Property::factory()->create(['is_featured' => true, 'featured_image_id' => $eager->id, 'order' => 1]);
        Property::factory()->create(['is_featured' => true, 'featured_image_id' => $lazy->id, 'order' => 2]);
        Property::factory()->create(['is_featured' => true, 'order' => 3]);

        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        // Exactly one LCP candidate on the homepage, and no preload there.
        $this->assertSame(
            1,
            substr_count($html, 'loading="eager" fetchpriority="high"'),
            'Only the first property card may be eager + high priority.'
        );
        $this->assertStringNotContainsString('rel="preload" as="image"', $html);
        // Real dimensions from media metadata, not fabricated values.
        $this->assertStringContainsString('width="1200" height="900"', $html);
        $this->assertStringContainsString('width="800" height="600"', $html);
    }

    // ==================== Lucide loading strategy ====================

    public function test_layout_lucide_script_is_pinned_and_deferred(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(
            'https://unpkg.com/lucide@1.42.0/dist/umd/lucide.min.js" defer',
            $html,
            'Lucide must be pinned and deferred.'
        );
        $this->assertStringNotContainsString('lucide@latest', $html);
    }
}
