<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_returns_json_with_published_results(): void
    {
        $author = User::factory()->create();

        $category = Category::create(['name' => 'News', 'slug' => 'news']);

        Post::factory()->published()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Modern Apartment Living Guide',
            'slug' => 'modern-apartment-living-guide',
        ]);
        Property::factory()->create([
            'name' => 'Skyline Apartment Tower',
            'slug' => 'skyline-apartment-tower',
            'status' => 'published',
        ]);
        Page::create([
            'title' => 'About Us Apartment',
            'slug' => 'about-us',
            'status' => 'published',
            'is_homepage' => false,
        ]);

        $response = $this->getJson('/search/suggest?q=apartment');

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                '*' => ['title', 'url', 'type'],
            ]])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'post');
    }

    public function test_suggest_excludes_unpublished_content(): void
    {
        $author = User::factory()->create();

        Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Draft Apartment Secrets',
            'slug' => 'draft-apartment-secrets',
            'status' => 'draft',
            'published_at' => null,
        ]);
        Property::factory()->create([
            'name' => 'Hidden Apartment',
            'slug' => 'hidden-apartment',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/search/suggest?q=apartment');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_suggest_rejects_short_query(): void
    {
        $response = $this->getJson('/search/suggest?q=a');

        $response->assertOk()->assertJsonStructure(['data'])
            ->assertJsonCount(0, 'data');
    }
}
