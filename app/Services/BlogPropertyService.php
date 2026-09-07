<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Property;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves published properties to feature on blog pages.
 *
 * Used for the article property CTA (blog.show) and the sidebar
 * "Properti Populer" block. Matching logic lives here — not in
 * controllers or Blade — per the Controller-Service pattern.
 */
class BlogPropertyService
{
    /** Eager-load set shared by every query so the card partial never N+1s. */
    public const CARD_WITH = ['featuredImage', 'photos.media', 'amenities'];

    /**
     * Properties to promote on a single post's page.
     *
     * 1. Location tags of the post are matched against config('blog.location_tag_to_city').
     * 2. Published properties in those cities, featured first.
     * 3. Not enough matches → backfilled with published featured properties.
     * 4. No location tag / no match at all → published featured properties.
     */
    public function forPost(Post $post, int $limit = 3): Collection
    {
        $cities = $this->citiesForPost($post);

        if ($cities === []) {
            return $this->featured($limit);
        }

        $properties = $this->publishedInCities($cities, $limit);

        if ($properties->count() < $limit) {
            $properties = $this->backfillWithFeatured($properties, $limit);
        }

        return $properties;
    }

    /**
     * Published featured properties (sidebar "Properti Populer").
     * Cached under the existing 'blog' cache tag with a 1-hour TTL,
     * mirroring the sidebar cache pattern in BlogController.
     */
    public function featured(int $limit = 3): Collection
    {
        $key = 'blog_featured_properties_'.$limit;

        return $this->rememberBlog($key, fn (): Collection => $this->publishedFeaturedQuery()->take($limit)->get());
    }

    /**
     * Properties to promote on a tag archive page.
     * Location tags resolve via config; any other tag falls back to featured.
     */
    public function propertiesForTag(Tag $tag, int $limit = 3): Collection
    {
        $cities = $this->citiesForTags([$tag->slug]);

        if ($cities === []) {
            return $this->featured($limit);
        }

        return $this->publishedInCities($cities, $limit);
    }

    /**
     * Cache helper mirroring BlogController::getSidebarData(): uses the 'blog'
     * cache tag when the driver supports tags, falls back to a plain remember
     * otherwise (file/database drivers).
     */
    protected function rememberBlog(string $key, callable $resolver): mixed
    {
        try {
            return Cache::tags(['blog'])->remember($key, now()->addHour(), $resolver);
        } catch (\BadMethodCallException $e) {
            return Cache::remember($key, now()->addHour(), $resolver);
        }
    }

    /**
     * Published properties in the given cities, featured first, then by order.
     * Eager loads everything the property card partial renders.
     */
    protected function publishedInCities(array $cities, int $limit): Collection
    {
        return Property::published()
            ->whereIn('city', $cities)
            ->with(self::CARD_WITH)
            ->orderBy('is_featured', 'desc')
            ->orderBy('order', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Base query for featured fallback properties.
     */
    protected function publishedFeaturedQuery(): Builder
    {
        return Property::published()
            ->featured()
            ->with(self::CARD_WITH)
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Top up a result set to $limit using published featured properties.
     */
    protected function backfillWithFeatured(Collection $properties, int $limit): Collection
    {
        $excludeIds = $properties->modelKeys();

        $extra = $this->publishedFeaturedQuery()
            ->whereNotIn('id', $excludeIds)
            ->take($limit - $properties->count())
            ->get();

        return $properties->concat($extra)->values();
    }

    /**
     * Distinct city values mapped from the post's location tags.
     *
     * @return string[]
     */
    protected function citiesForPost(Post $post): array
    {
        return $this->citiesForTags($post->tags->pluck('slug')->all());
    }

    /**
     * Map tag slugs to city values via config('blog.location_tag_to_city').
     *
     * @param  string[]  $tagSlugs
     * @return string[]
     */
    protected function citiesForTags(array $tagSlugs): array
    {
        $map = (array) config('blog.location_tag_to_city', []);

        $cities = [];

        foreach ($tagSlugs as $slug) {
            foreach ((array) ($map[$slug] ?? []) as $city) {
                if ($city !== '') {
                    $cities[$city] = true;
                }
            }
        }

        return array_keys($cities);
    }
}
