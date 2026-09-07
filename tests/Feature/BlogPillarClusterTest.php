<?php

namespace Tests\Feature;

use App\Http\Controllers\BlogController;
use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pillar → cluster architecture (Fase 5): editorial relationship, admin
 * control, public discovery modules, draft handling, limit and dedupe.
 */
class BlogPillarClusterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user = User::factory()->create();
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
    }

    // ==================== Helpers ====================

    private function makePost(string $slug, array $attributes = []): Post
    {
        return Post::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now()->subDays(Str::length($slug)),
        ], $attributes));
    }

    private function adminUpdatePayload(Post $post, array $overrides = []): array
    {
        return array_merge([
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => '<p>Updated content.</p>',
            'excerpt' => $post->excerpt,
            'status' => 'published',
            'category_id' => $post->category_id,
            // ConvertEmptyStringsToNull turns '' into null; the real form
            // always submits a tag string, so mirror that here.
            'tags' => 'test-tag',
        ], $overrides);
    }

    /**
     * HTML slice from $startNeedle up to (not including) the next
     * $endNeedle — scopes assertions to one page section.
     */
    private function sectionBetween(string $html, string $startNeedle, string $endNeedle): string
    {
        $start = strpos($html, $startNeedle);
        if ($start === false) {
            return '';
        }

        $end = strpos($html, $endNeedle, $start);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    // ==================== Model relationship ====================

    public function test_post_can_have_a_pillar(): void
    {
        $pillar = $this->makePost('pillar-post');
        $cluster = $this->makePost('cluster-post', ['pillar_post_id' => $pillar->id]);

        $this->assertTrue($cluster->pillar->is($pillar));
    }

    public function test_pillar_can_have_cluster_posts(): void
    {
        $pillar = $this->makePost('pillar-post');
        $clusterA = $this->makePost('cluster-a', ['pillar_post_id' => $pillar->id]);
        $clusterB = $this->makePost('cluster-b', ['pillar_post_id' => $pillar->id]);

        $clusterIds = $pillar->clusterPosts->modelKeys();

        $this->assertContains($clusterA->id, $clusterIds);
        $this->assertContains($clusterB->id, $clusterIds);
        $this->assertCount(2, $clusterIds);
    }

    // ==================== Admin editorial control ====================

    public function test_admin_can_assign_a_pillar(): void
    {
        $post = $this->makePost('cluster-post');
        $pillar = $this->makePost('pillar-post');

        $response = $this->actingAs($this->user)
            ->patch(route('admin.posts.update', $post), $this->adminUpdatePayload($post, [
                'pillar_post_id' => $pillar->id,
            ]));

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'pillar_post_id' => $pillar->id]);
    }

    public function test_admin_can_remove_a_pillar(): void
    {
        $pillar = $this->makePost('pillar-post');
        $post = $this->makePost('cluster-post', ['pillar_post_id' => $pillar->id]);

        $response = $this->actingAs($this->user)
            ->patch(route('admin.posts.update', $post), $this->adminUpdatePayload($post, [
                'pillar_post_id' => '',
            ]));

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'pillar_post_id' => null]);
    }

    public function test_admin_cannot_assign_a_nonexistent_pillar(): void
    {
        $post = $this->makePost('cluster-post');

        $response = $this->actingAs($this->user)
            ->patch(route('admin.posts.update', $post), $this->adminUpdatePayload($post, [
                'pillar_post_id' => 999999,
            ]));

        $response->assertSessionHasErrors('pillar_post_id');
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'pillar_post_id' => null]);
    }

    public function test_admin_cannot_assign_the_post_itself_as_pillar(): void
    {
        $post = $this->makePost('cluster-post');

        $response = $this->actingAs($this->user)
            ->patch(route('admin.posts.update', $post), $this->adminUpdatePayload($post, [
                'pillar_post_id' => $post->id,
            ]));

        $response->assertSessionHasErrors('pillar_post_id');
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'pillar_post_id' => null]);
    }

    // ==================== Public: cluster article → pillar ====================

    public function test_cluster_article_shows_published_pillar_section(): void
    {
        $pillar = $this->makePost('panduan-lengkap');
        $cluster = $this->makePost('cluster-post', ['pillar_post_id' => $pillar->id]);

        $response = $this->get(route('blog.show', $cluster->slug));

        $response->assertOk();
        $response->assertSee(__('blog.part_of_guide'), false);
        $response->assertSee(e($pillar->title), false);
        $response->assertSee(route('blog.show', $pillar->slug), false);
    }

    public function test_draft_pillar_is_not_shown_on_public_page(): void
    {
        $pillar = $this->makePost('draft-pillar', ['status' => 'draft', 'published_at' => null]);
        $cluster = $this->makePost('cluster-post', ['pillar_post_id' => $pillar->id]);

        $response = $this->get(route('blog.show', $cluster->slug));

        $response->assertOk();
        $response->assertDontSee(__('blog.part_of_guide'), false);
        $response->assertDontSee(e($pillar->title), false);
    }

    // ==================== Public: pillar article → cluster list ====================

    public function test_pillar_article_lists_published_cluster_posts_only(): void
    {
        $pillar = $this->makePost('pillar-post');
        $clusterA = $this->makePost('cluster-a', ['pillar_post_id' => $pillar->id, 'published_at' => now()->subDays(3)]);
        $clusterB = $this->makePost('cluster-b', ['pillar_post_id' => $pillar->id, 'published_at' => now()->subDays(2)]);
        $this->makePost('cluster-draft', ['pillar_post_id' => $pillar->id, 'status' => 'draft', 'published_at' => null]);

        $response = $this->get(route('blog.show', $pillar->slug));

        $response->assertOk();
        $response->assertSee(__('blog.in_this_guide'), false);
        $response->assertSee(e($clusterA->title), false);
        $response->assertSee(e($clusterB->title), false);
        $response->assertDontSee('cluster-draft', false);
    }

    public function test_pillar_article_is_never_listed_as_its_own_cluster(): void
    {
        $pillar = $this->makePost('pillar-post');
        $this->makePost('cluster-a', ['pillar_post_id' => $pillar->id]);

        $response = $this->get(route('blog.show', $pillar->slug));

        $response->assertOk();
        $section = $this->sectionBetween($response->getContent(), 'cluster-heading', '</section>');
        // The list never links back to the pillar itself.
        $this->assertSame(0, substr_count($section, 'href="'.route('blog.show', $pillar->slug).'"'));
    }

    public function test_cluster_list_respects_the_configured_limit(): void
    {
        config(['blog.cluster_posts_limit' => 2]);

        $pillar = $this->makePost('pillar-post');
        $this->makePost('cluster-a', ['pillar_post_id' => $pillar->id, 'published_at' => now()->subDays(3)]);
        $this->makePost('cluster-b', ['pillar_post_id' => $pillar->id, 'published_at' => now()->subDays(2)]);
        $this->makePost('cluster-c', ['pillar_post_id' => $pillar->id, 'published_at' => now()->subDay()]);

        $response = $this->get(route('blog.show', $pillar->slug));

        $response->assertOk();
        $section = $this->sectionBetween($response->getContent(), 'cluster-heading', '</section>');
        $this->assertSame(2, substr_count($section, '<li>'));
    }

    // ==================== No relationship ====================

    public function test_standalone_post_renders_without_pillar_sections(): void
    {
        $post = $this->makePost('standalone-post');

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertDontSee(__('blog.part_of_guide'), false);
        $response->assertDontSee(__('blog.in_this_guide'), false);
    }

    // ==================== Related posts interaction ====================

    public function test_pillar_is_not_repeated_in_related_posts(): void
    {
        // Cluster in its own category; its only same-category neighbour is
        // the pillar → related Q1 would return exactly the pillar.
        $category = Category::create(['name' => 'Guide Cat', 'slug' => 'guide-cat']);
        $pillar = $this->makePost('pillar-post', ['category_id' => $category->id]);
        $cluster = $this->makePost('cluster-post', ['category_id' => $category->id, 'pillar_post_id' => $pillar->id]);

        // Controller-level dedup behaviour via the dedicated method.
        $controller = new BlogController;
        $method = new \ReflectionMethod($controller, 'dedupPillarRelated');
        $filtered = $method->invoke($controller, $cluster->newCollection([$pillar]), $cluster);

        $this->assertTrue($filtered->isEmpty());

        // Related section on the page does not render the pillar link
        // (scope assertions to the related block — sidebar lists posts too).
        $response = $this->get(route('blog.show', $cluster->slug));
        $response->assertOk();
        $relatedSection = $this->sectionBetween($response->getContent(), __('blog.related'), 'grid');
        $this->assertStringNotContainsString('href="'.route('blog.show', $pillar->slug).'"', $relatedSection);
    }

    // ==================== Regression ====================

    public function test_related_posts_and_property_cta_still_render_with_pillar_data(): void
    {
        $category = Category::create(['name' => 'Regression Cat', 'slug' => 'regression-cat']);
        $pillar = $this->makePost('pillar-post', ['category_id' => $category->id]);
        $sibling = $this->makePost('sibling-post', ['category_id' => $category->id]);
        $cluster = $this->makePost('cluster-post', ['category_id' => $category->id, 'pillar_post_id' => $pillar->id]);

        $response = $this->get(route('blog.show', $cluster->slug));

        $response->assertOk();
        // Related: sibling (same category, not the excluded pillar).
        $response->assertSee(e($sibling->title), false);
        // Article schema still present.
        $response->assertSee('"@type": "Article"', false);
        // Pillar section still present.
        $response->assertSee(__('blog.part_of_guide'), false);
    }
}
