<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_transactions_but_keeps_catalogue_and_users(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '20.00']);
        $shift = app(ShiftService::class)->open($user, '100.00');

        app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 1]],
            PaymentMethod::Cash,
            '20.00',
        );

        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        CustomerFeedback::create([
            'order_id' => $order->id,
            'phone' => $order->customer_phone,
            'rating' => 5,
        ]);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('customer_feedback', 1);
        $this->assertDatabaseCount('shifts', 1);

        $this->artisan('db:clear-transactions', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('customer_feedback', 0);
        $this->assertDatabaseCount('shifts', 0);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
