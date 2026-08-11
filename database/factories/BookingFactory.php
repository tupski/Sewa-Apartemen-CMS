<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    private static int $codeCounter = 0;

    public function definition(): array
    {
        self::$codeCounter++;

        $checkIn = $this->faker->dateTimeBetween('now', '+1 month');
        $checkOut = (clone $checkIn)->modify('+' . $this->faker->numberBetween(1, 14) . ' days');

        return [
            'unit_id' => Unit::factory(),
            'property_id' => Property::factory(),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $this->faker->numberBetween(1, 4),
            'code' => 'BK-' . now()->format('Ymd') . '-' . str_pad(self::$codeCounter, 4, '0', STR_PAD_LEFT),
            'status' => $this->faker->randomElement(['pending', 'confirmed']),
            'total_price' => $this->faker->randomFloat(2, 500000, 10000000),
            'deposit_amount' => $this->faker->randomFloat(2, 150000, 3000000),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'confirmed']);
    }
}
