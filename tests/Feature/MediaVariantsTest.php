<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\Role;
use App\Models\User;
use App\Services\ImageVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CWV Phase B: responsive image variants (400/800/1600 + WebP/AVIF).
 *
 * Covers:
 *  - ImageVariantService: real GD generation against a fixture image,
 *    metadata recording, idempotence, force regeneration, non-raster skip
 *  - Media model: variants()/srcsetFor()/deleteFile() variant cleanup
 *  - Upload pipeline hook: variants generated on media upload
 *  - x-media-image component: <picture>/srcset/sizes markup, LCP attrs,
 *    graceful degradation without variants
 *  - Property detail: preload uses imagesrcset matching the <picture> sources
 */
class MediaVariantsTest extends TestCase
{
    use RefreshDatabase;

    private ImageVariantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImageVariantService::class);
        Storage::fake('public');
    }

    /**
     * Create a real JPEG on the fake disk (GD decodes the actual bytes).
     */
    protected function makeImageMedia(int $width = 2000, int $height = 1200, array $overrides = []): Media
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 80, 120, 200));

        ob_start();
        imagejpeg($image, null, 90);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        $directory = 'properties/test';
        $filename = 'gen-'.uniqid().'.jpg';
        Storage::disk('public')->put($directory.'/'.$filename, $bytes);

        return Media::create(array_merge([
            'disk' => 'public',
            'directory' => $directory,
            'filename' => $filename,
            'original_filename' => 'gen.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => strlen($bytes),
            'type' => 'image',
            'width' => $width,
            'height' => $height,
        ], $overrides));
    }

    // ==================== ImageVariantService ====================

    public function test_generates_width_and_modern_format_variants(): void
    {
        $media = $this->makeImageMedia(width: 2000, height: 1200);

        $variants = $this->service->generateFor($media);

        // Original + 400/800/1600 below the 2000px source.
        $this->assertCount(4, $variants['original']);
        $this->assertSame(2000, $variants['original'][0]['width']);
        $this->assertSame([400, 800, 1600], array_column(array_slice($variants['original'], 1), 'width'));

        // WebP + AVIF ladders generated for a JPEG source (GD supports both here).
        $this->assertArrayHasKey('webp', $variants);
        $this->assertCount(3, $variants['webp']);
        $this->assertArrayHasKey('avif', $variants);
        $this->assertCount(3, $variants['avif']);

        // Files really exist on the disk and entries carry the right URL.
        foreach (['original', 'webp', 'avif'] as $group) {
            foreach ($variants[$group] as $entry) {
                Storage::disk('public')->assertExists($entry['path']);
                $this->assertSame(
                    Storage::disk('public')->url($entry['path']),
                    $entry['url']
                );
            }
        }

        // Metadata persisted (variants readable back from the model).
        $media->refresh();
        $this->assertTrue($media->hasVariants());
        $this->assertCount(4, $media->variants()['original']);
    }

    public function test_small_originals_only_get_variants_below_their_width(): void
    {
        $media = $this->makeImageMedia(width: 600, height: 400);

        $variants = $this->service->generateFor($media);

        // No upscaling: only the 400px rung below 600.
        $this->assertSame([400], array_column(array_slice($variants['original'], 1), 'width'));
    }

    public function test_generation_is_idempotent_unless_forced(): void
    {
        $media = $this->makeImageMedia();

        $first = $this->service->generateFor($media);
        $countAfterFirst = count(Storage::disk('public')->allFiles('properties/test'));

        $second = $this->service->generateFor($media);
        $this->assertSame($first, $second, 'Second run must be a no-op returning stored metadata.');
        $this->assertSame(
            $countAfterFirst,
            count(Storage::disk('public')->allFiles('properties/test')),
            'No new files written on the second run.'
        );

        $this->service->generateFor($media, force: true);
        // Force run rewrote the same set of files — everything still present.
        foreach ($first as $entries) {
            foreach ($entries as $entry) {
                Storage::disk('public')->assertExists($entry['path']);
            }
        }
    }

    public function test_non_raster_media_is_skipped(): void
    {
        $svg = Media::create([
            'disk' => 'public',
            'directory' => 'misc',
            'filename' => 'logo-'.uniqid().'.svg',
            'original_filename' => 'logo.svg',
            'mime_type' => 'image/svg+xml',
            'extension' => 'svg',
            'size' => 100,
            'type' => 'image',
        ]);

        $this->assertSame([], $this->service->generateFor($svg));
        $this->assertFalse($svg->hasVariants());
    }

    public function test_missing_source_file_is_skipped(): void
    {
        $media = $this->makeImageMedia();
        Storage::disk('public')->delete($media->directory.'/'.$media->filename);

        $this->assertSame([], $this->service->generateFor($media, force: true));
    }

    // ==================== Media model ====================

    public function test_srcset_is_sorted_and_wellformed(): void
    {
        $media = $this->makeImageMedia(width: 2000, height: 1200);
        $this->service->generateFor($media);
        $media->refresh();

        $srcset = $media->srcsetFor('original');

        $this->assertStringContainsString('-400w.jpg 400w', $srcset);
        $this->assertStringContainsString('-800w.jpg 800w', $srcset);
        $this->assertStringContainsString('-1600w.jpg 1600w', $srcset);

        // Ascending width order.
        preg_match_all('/(\d+)w/', $srcset, $matches);
        $widths = array_map('intval', $matches[1]);
        $sorted = $widths;
        sort($sorted);
        $this->assertSame($sorted, $widths);

        // WebP group produces .webp URLs.
        $this->assertStringContainsString('.webp 400w', $media->srcsetFor('webp'));
    }

    public function test_srcset_empty_without_variants(): void
    {
        $media = $this->makeImageMedia();
        $this->assertSame('', $media->srcsetFor('original'));
    }

    public function test_delete_file_removes_variant_files(): void
    {
        $media = $this->makeImageMedia(width: 2000, height: 1200);
        $this->service->generateFor($media);

        $variantPaths = collect($this->service->generateFor($media))
            ->flatMap(fn ($entries) => collect($entries)->pluck('path'))
            ->filter(fn ($p) => str_contains((string) $p, '/variants/'))
            ->unique()
            ->values();
        $this->assertNotEmpty($variantPaths);

        $media->deleteFile();

        foreach ($variantPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        // deleteFile() removes the original too (record deletion is the caller's decision).
        Storage::disk('public')->assertMissing($media->directory.'/'.$media->filename);
    }

    // ==================== Upload pipeline hook ====================

    public function test_upload_generates_variants_automatically(): void
    {
        $admin = User::factory()->create();
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($admin);

        $image = imagecreatetruecolor(1800, 1000);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 60, 60));
        ob_start();
        imagejpeg($image, null, 90);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        $tmp = tempnam(sys_get_temp_dir(), 'up').'jpg';
        file_put_contents($tmp, $bytes);

        $response = $this->postJson(route('admin.media.upload'), [
            'files' => [
                new UploadedFile($tmp, 'upload-test.jpg', 'image/jpeg', null, true),
            ],
        ]);

        $response->assertCreated();
        $payload = $response->json('uploaded.0');
        $this->assertNotNull($payload);

        $media = Media::findOrFail($payload['id'] ?? 0)->fresh();
        $this->assertNotNull($media, 'Uploaded media record must exist.');
        $this->assertTrue($media->hasVariants(), 'Upload must trigger variant generation.');
        $this->assertNotSame('', $media->srcsetFor('webp'), 'WebP ladder must be present after upload.');
    }

    // ==================== x-media-image component ====================

    public function test_component_renders_picture_with_variants(): void
    {
        $media = $this->makeImageMedia(width: 2000, height: 1200);
        $this->service->generateFor($media);
        $media->refresh();

        $html = $this->blade(
            '<x-media-image :media="$media" alt="Foto test" sizes="(min-width: 640px) 50vw, 100vw" class="w-full"/>',
            ['media' => $media]
        );

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('type="image/avif"', $html);
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('srcset="'.e($media->srcsetFor('avif')).'"', $html);
        $this->assertStringContainsString('sizes="(min-width: 640px) 50vw, 100vw"', $html);
        // Intrinsic dimensions + lazy default.
        $this->assertStringContainsString('width="2000" height="1200"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('fetchpriority', $html);
        $this->assertStringContainsString('alt="Foto test"', $html);
    }

    public function test_component_eager_renders_lcp_attributes(): void
    {
        $media = $this->makeImageMedia();
        $this->service->generateFor($media);
        $media->refresh();

        $html = $this->blade(
            '<x-media-image :media="$media" eager class="object-cover"/>',
            ['media' => $media]
        );

        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringNotContainsString('loading="lazy"', $html);
    }

    public function test_component_degrades_to_plain_img_without_variants(): void
    {
        $media = $this->makeImageMedia(900, 600, ['width' => null, 'height' => null]);

        $html = $this->blade(
            '<x-media-image :media="$media" alt="Fallback"/>',
            ['media' => $media]
        );

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringNotContainsString('srcset=', $html);
        $this->assertStringNotContainsString('<source', $html);
        $this->assertStringNotContainsString('width="', $html, 'No fabricated dimensions.');
        $this->assertStringContainsString('src="'.e($media->url).'"', $html);
    }

    public function test_component_renders_nothing_for_null_media(): void
    {
        $html = $this->blade('<x-media-image :media="null" alt="x"/>', []);
        $this->assertSame('', trim($html));
    }

    // ==================== Property detail integration ====================

    public function test_property_detail_preload_matches_picture_selection(): void
    {
        $property = Property::factory()->create();
        $media = $this->makeImageMedia(width: 2000, height: 1200);
        PropertyPhoto::create([
            'property_id' => $property->id,
            'media_id' => $media->id,
            'category' => 'Interior',
            'sort_order' => 1,
        ]);
        $this->service->generateFor($media);
        $media->refresh();

        $html = $this->get(route('properties.public.show', $property->slug))
            ->assertOk()
            ->getContent();

        // Exactly one LCP image preload, using imagesrcset when variants exist.
        $this->assertSame(1, substr_count($html, 'rel="preload" as="image"'));
        $this->assertStringContainsString('imagesrcset="'.e($media->srcsetFor('avif')).'"', $html);
        $this->assertStringContainsString('type="image/avif"', $html);
        $this->assertStringContainsString('imagesizes="(min-width: 768px) 25vw, 100vw"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);

        // The <img> itself is srcset-aware.
        $this->assertStringContainsString('srcset="'.e($media->srcsetFor('original')).'"', $html);
    }

    public function test_property_detail_preload_stays_simple_without_variants(): void
    {
        $property = Property::factory()->create();
        $image = imagecreatetruecolor(300, 200);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 10, 10));
        ob_start();
        imagejpeg($image, null, 80);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $path = 'properties/test/plain-'.uniqid().'.jpg';
        Storage::disk('public')->put($path, $bytes);
        $media = Media::create([
            'disk' => 'public',
            'directory' => 'properties/test',
            'filename' => basename($path),
            'original_filename' => 'plain.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => strlen($bytes),
            'type' => 'image',
            'width' => 300,
            'height' => 200,
        ]);
        PropertyPhoto::create([
            'property_id' => $property->id,
            'media_id' => $media->id,
            'category' => 'Interior',
            'sort_order' => 1,
        ]);

        $html = $this->get(route('properties.public.show', $property->slug))
            ->assertOk()
            ->getContent();

        // One preload, plain href (no imagesrcset), original URL — LCP still prioritised.
        $this->assertSame(1, substr_count($html, 'rel="preload" as="image"'));
        $this->assertStringNotContainsString('imagesrcset', $html);
        $this->assertStringContainsString('href="'.e($media->url).'"', $html);
    }
}
