<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
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
            'category_id' => Category::inRandomOrder()->first()->id ?? 1,
            'name' => $this->faker->word(2, true),
            'sku' => 'PRD-' . $this->faker->unique()->numberBetween(10000, 99999),
            'price' => $this->faker->numberBetween('2000', '50000'),
            'stock' => $this->faker->numberBetween(5, 100),
        ];
    }
}
