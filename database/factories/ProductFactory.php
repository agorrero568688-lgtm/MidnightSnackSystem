<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $cost = fake()->numberBetween(5, 50);
        $price = $cost + fake()->numberBetween(3, 25);

        return [
            'category_id' => Category::factory(),
            'sku' => mb_strtoupper(fake()->unique()->bothify('SKU-####??')),
            'barcode' => fake()->unique()->ean13(),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'unit' => 'serving',
            'cost_price' => $cost.'.00',
            'selling_price' => $price.'.00',
            'is_available' => true,
            'is_active' => true,
            'is_online' => true,
            'sort_order' => 0,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => ['is_available' => false]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
