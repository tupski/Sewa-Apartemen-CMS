<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Related-posts behaviour on the public article page:
 * category-first, shared-tag fallback, dedupe, exclusion and drafts.
 */
class BlogRelatedPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ==================== Helpers ====================

    private function makePost(string $slug, ?Category $category = null, array $tagSlugs = [], string $status = 'published'): Post
    {
        $category ??= Category::create(['name' => 'Cat '.$slug, 'slug' => 'cat-'.Str::slug($slug)]);

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'slug' => $slug,
            'status' => $status,
            'published_at' => $status === 'published' ? now()->subMinutes(5) : null,
        ]);

        foreach ($tagSlugs as $tagSlug) {
            $tag = Tag::firstOrCreate(['slug' => $tagSlug], ['name' => ucfirst($tagSlug)]);
            $post->tags()->syncWithoutDetaching([$tag->id]);
        }

        return $post->refresh();
    }

    /**
     * Post slugs rendered inside the "Related Posts" section of the article
     * page. Section is cut before the sidebar container (`lg:w-80`) so
     * sidebar links (recent posts, categories, tags) never leak into the
     * assertion window.
     */
    private function relatedSlugsOnPage(Post $post): array
    {
        $response = $this->get(route('blog.show', $post->slug));
        $response->assertOk();

        $html = $response->getContent();

        if (! preg_match('/Artikel Terkait(.*?)(?:lg:w-80|$)/s', $html, $m)) {
            return [];
        }

        preg_match_all('/blog\/([a-z0-9\-]+)/', $m[1], $links);

        return array_values(array_unique($links[1]));
    }

    // ==================== Tests ====================

    public function test_category_related_posts_are_shown(): void
    {
        $category = Category::create(['name' => 'Tips', 'slug' => 'tips']);
        $current = $this->makePost('current-post', $category);
        $siblingA = $this->makePost('sibling-a', $category);
        $siblingB = $this->makePost('sibling-b', $category);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertContains('sibling-a', $related);
        $this->assertContains('sibling-b', $related);
    }

    public function test_shared_tag_fallback_fills_up_to_three(): void
    {
        // Current post is the only one in its category, but shares tags
        // with posts in other categories.
        $current = $this->makePost('current-post', tagSlugs: ['jakarta', 'transit']);
        $byTagA = $this->makePost('by-tag-a', tagSlugs: ['transit']);
        $byTagB = $this->makePost('by-tag-b', tagSlugs: ['jakarta', 'transit']);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertContains('by-tag-a', $related);
        $this->assertContains('by-tag-b', $related);
        $this->assertCount(2, $related);
        $this->assertNotContains('current-post', $related);
    }

    public function test_related_posts_are_capped_at_three(): void
    {
        $category = Category::create(['name' => 'Tips', 'slug' => 'tips']);
        $current = $this->makePost('current-post', $category, ['jakarta']);
        $this->makePost('cat-a', $category, ['jakarta']);
        $this->makePost('cat-b', $category, ['jakarta']);
        $this->makePost('cat-c', $category, ['jakarta']);
        $this->makePost('cat-d', $category, ['jakarta']);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertCount(3, $related);
    }

    public function test_no_duplicate_between_category_and_tag_fallback(): void
    {
        $category = Category::create(['name' => 'Tips', 'slug' => 'tips']);
        $current = $this->makePost('current-post', $category, ['jakarta']);
        $sibling = $this->makePost('sibling-shared-tag', $category, ['jakarta']);
        // Two more tagged posts — the sibling must not be picked again
        // by the fallback after being selected via category.
        $this->makePost('tag-x', tagSlugs: ['jakarta']);
        $this->makePost('tag-y', tagSlugs: ['jakarta']);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertCount(1, array_keys($related, 'sibling-shared-tag'));
        $this->assertContains('sibling-shared-tag', $related);
        $this->assertContains('tag-x', $related);
        $this->assertContains('tag-y', $related);
    }

    public function test_current_post_never_appears_as_related(): void
    {
        $current = $this->makePost('current-post', tagSlugs: ['jakarta']);
        $this->makePost('other-post', tagSlugs: ['jakarta']);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertNotContains('current-post', $related);
    }

    public function test_draft_posts_never_appear_as_related(): void
    {
        $category = Category::create(['name' => 'Tips', 'slug' => 'tips']);
        $current = $this->makePost('current-post', $category, ['jakarta']);
        $this->makePost('cat-draft', $category, [], 'draft');
        $this->makePost('tag-draft', tagSlugs: ['jakarta'], status: 'draft');
        $published = $this->makePost('cat-published', $category);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertContains('cat-published', $related);
        $this->assertNotContains('cat-draft', $related);
        $this->assertNotContains('tag-draft', $related);
    }

    public function test_no_shared_tags_and_no_siblings_yields_no_related_section(): void
    {
        $current = $this->makePost('current-post', tagSlugs: ['unique-tag-xyz']);
        $this->makePost('unrelated', tagSlugs: ['different-tag']);

        $related = $this->relatedSlugsOnPage($current);

        $this->assertSame([], $related);
    }
}
