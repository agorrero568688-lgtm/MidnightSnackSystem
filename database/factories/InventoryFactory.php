<?php

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category' => fake()->randomElement(Inventory::CATEGORIES),
            'quantity' => fake()->randomFloat(3, 1, 50),
            'unit' => fake()->randomElement(Inventory::UNITS),
            'reorder_level' => fake()->randomFloat(3, 0.5, 5),
            'cost_per_unit' => fake()->randomFloat(2, 10, 500),
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0.5,
            'reorder_level' => 2,
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
