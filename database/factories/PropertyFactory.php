<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company() . ' Apartments';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'status' => 'published',
            'unit_types' => ['studio', '1br'],
            'weekend_days' => [6, 0],
            'prices' => [
                'studio' => ['night_wd' => 500000, 'night_we' => 600000, 't3_wd' => 150000, 't3_we' => 180000, 'weekly' => 3000000, 'monthly' => 9000000],
                '1br' => ['night_wd' => 700000, 'night_we' => 800000, 't3_wd' => 200000, 't3_we' => 240000, 'weekly' => 4200000, 'monthly' => 12600000],
            ],
        ];
    }
}
