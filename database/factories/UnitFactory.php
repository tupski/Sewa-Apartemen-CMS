<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $name = 'Unit ' . $this->faker->unique()->bothify('##?');

        return [
            'property_id' => Property::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'unit_type' => $this->faker->randomElement(['Studio', '1BR', '2BR', '3BR']),
            'floor' => $this->faker->numberBetween(1, 30),
            'size_sqm' => $this->faker->randomFloat(2, 20, 150),
            'bedrooms' => $this->faker->numberBetween(1, 3),
            'bathrooms' => $this->faker->numberBetween(1, 2),
            'price_per_night' => $this->faker->randomFloat(2, 200000, 2000000),
            'status' => 'available',
        ];
    }

    public function booked(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'booked',
        ]);
    }
}
