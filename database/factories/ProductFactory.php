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
            'unit' => 'pc',
            'cost_price' => $cost.'.00',
            'selling_price' => $price.'.00',
            'stock_qty' => 50,
            'low_stock_threshold' => 5,
            'track_stock' => true,
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock_qty' => 0]);
    }

    public function untracked(): static
    {
        return $this->state(fn (array $attributes) => ['track_stock' => false]);
    }
}
