<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $unit = fake()->numberBetween(3000, 20000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'name_snapshot' => fake()->words(3, true),
            'sku_snapshot' => mb_strtoupper(fake()->bothify('SKU-####')),
            'unit_price' => Money::decimal($unit),
            'qty' => $qty,
            'line_total' => Money::decimal($unit * $qty),
            'note' => null,
        ];
    }
}
