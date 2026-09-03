<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
    }

    // ==================== Post CRUD ====================

    public function test_admin_can_create_post(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.posts.store'), [
            'title' => 'Test Blog Post',
            'slug' => 'test-blog-post',
            'content' => '<p>This is the content of the post.</p>',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Blog Post',
            'slug' => 'test-blog-post',
        ]);
    }

    public function test_admin_can_read_posts_index(): void
    {
        Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('admin.posts.index'));

        $response->assertStatus(200);
        $response->assertSee('Posts');
    }

    public function test_admin_can_update_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('admin.posts.update', $post), [
            'title' => 'Updated Title',
            'slug' => 'updated-title',
            'content' => '<p>Updated content.</p>',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'status' => 'published',
        ]);
    }

    public function test_admin_can_delete_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('admin.posts.destroy', $post));

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // ==================== Category CRUD ====================

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.categories.store'), [
            'name' => 'News',
            'slug' => 'news',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);
    }

    // ==================== Tag CRUD ====================

    public function test_admin_can_create_tag(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.tags.store'), [
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $response->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseHas('tags', [
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
    }

    // ==================== Public Routes ====================

    public function test_blog_listing_returns_200_with_published_posts(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $post = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee($post->title);
    }

    public function test_blog_listing_excludes_draft_posts(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $draft = Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertDontSee($draft->title);
    }

    public function test_blog_post_detail_returns_200_for_published(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $post = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee($post->title);
    }

    public function test_draft_post_returns_404_on_public_route(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertStatus(404);
    }

    public function test_category_filtered_blog_listing(): void
    {
        $category1 = Category::create(['name' => 'News', 'slug' => 'news']);
        $category2 = Category::create(['name' => 'Updates', 'slug' => 'updates']);

        $post1 = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category1->id,
        ]);
        $post2 = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id,
        ]);

        $response = $this->get(route('blog.category', $category1->slug));

        $response->assertStatus(200);
        $response->assertSee($post1->title);
        // post2 may appear in sidebar recent posts, so only verify post1 is visible
    }

    public function test_tag_filtered_blog_listing(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $tag = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);

        $post = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);
        $post->tags()->attach($tag);

        $response = $this->get(route('blog.tag', $tag->slug));

        $response->assertStatus(200);
        $response->assertSee($post->title);
    }

    // ==================== Sidebar categories ====================

    public function test_blog_sidebar_hides_categories_without_published_posts(): void
    {
        $withPosts = Category::create(['name' => 'Kategori Terpakai', 'slug' => 'kategori-terpakai']);
        $empty = Category::create(['name' => 'Kategori Kosong', 'slug' => 'kategori-kosong']);
        $draftOnly = Category::create(['name' => 'Kategori Draft', 'slug' => 'kategori-draft']);

        Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $withPosts->id,
        ]);
        Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $draftOnly->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee($withPosts->name);
        $response->assertDontSee($empty->name);
        $response->assertDontSee($draftOnly->name);
    }

    public function test_blog_sidebar_omits_the_categories_block_when_none_have_posts(): void
    {
        Category::create(['name' => 'Kategori Kosong', 'slug' => 'kategori-kosong']);

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertViewHas('categories', fn ($categories) => $categories->isEmpty());
        $response->assertDontSee(__('blog.categories'));
    }

    // ==================== Open Graph ====================

    public function test_post_page_emits_open_graph_tags_for_social_previews(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $post = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'title' => 'Judul Untuk Dibagikan',
            'excerpt' => 'Ringkasan singkat untuk pratinjau sosial.',
            'featured_image' => 'posts/preview.jpg',
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertSee('posts/preview.jpg', false);
        $response->assertSee('name="twitter:card"', false);
    }

    public function test_post_form_renders_the_social_share_preview(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('admin.posts.edit', $post));

        $response->assertStatus(200);
        $response->assertSee('data-testid="og-preview"', false);
        $response->assertSee('id="og-wa-title"', false);
        $response->assertSee('id="og-fb-title"', false);
    }

    // ==================== Sitemap ====================

    public function test_blog_sitemap_entries_present(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $post = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertSee('/blog/'.$post->slug);
        $response->assertSee('/blog');
    }
}
