<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => strtoupper($this->faker->unique()->bothify('PRD-####')),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'tax_percentage' => $this->faker->randomElement([0, 5, 12, 18, 28]),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }
}
