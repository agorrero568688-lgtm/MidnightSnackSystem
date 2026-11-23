<?php

namespace Database\Factories;

use App\Enums\OrderPaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10000, 90000);
        $delivery = 3900;

        return [
            'reference_no' => 'ORD-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'public_token' => Str::random(48),
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => OrderPaymentMethod::GCash,
            'order_type' => OrderType::Delivery,
            'customer_name' => fake()->name(),
            'customer_phone' => '0917'.fake()->numerify('#######'),
            'customer_email' => null,
            'delivery_address' => fake()->address(),
            'notes' => null,
            'subtotal' => Money::decimal($subtotal),
            'delivery_fee' => Money::decimal($delivery),
            'total' => Money::decimal($subtotal + $delivery),
            'placed_at' => now(),
        ];
    }

    public function pickup(): static
    {
        return $this->state(fn () => [
            'order_type' => OrderType::Pickup,
            'payment_method' => OrderPaymentMethod::CashOnPickup,
            'delivery_address' => null,
            'delivery_fee' => '0.00',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function awaitingVerification(): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::AwaitingVerification,
            'payment_reference' => (string) fake()->numerify('############'),
        ]);
    }
}
