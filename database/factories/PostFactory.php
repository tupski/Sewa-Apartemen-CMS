<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->sentence(10),
            'content' => '<p>' . implode('</p><p>', $this->faker->paragraphs(3)) . '</p>',
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }
}
