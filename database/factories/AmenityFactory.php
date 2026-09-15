<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'icon' => 'fa-'.Str::slug($this->faker->word()),
            'category' => $this->faker->randomElement(['property', 'unit']),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function property(): static
    {
        return $this->state(fn (): array => ['category' => 'property']);
    }

    public function unit(): static
    {
        return $this->state(fn (): array => ['category' => 'unit']);
    }
}
