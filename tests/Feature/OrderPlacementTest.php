<?php

namespace Tests\Feature;

use App\Enums\OrderPaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::fake();

        Setting::setMany([
            'online_ordering_enabled' => '1',
            'orders_paused' => '0',
            'delivery_enabled' => '1',
            'pickup_enabled' => '1',
            'delivery_fee' => '39.00',
            'free_delivery_threshold' => '0',
            'minimum_order_total' => '0',
            'allow_negative_stock' => '0',
            'order_alerts_enabled' => '0',
        ]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'selling_price' => '50.00',
            'stock_qty' => 10,
            'is_active' => true,
            'is_online' => true,
            'track_stock' => true,
        ], $attributes));
    }

    /** @return array<string, mixed> */
    private function deliveryPayload(Product $product, int $qty = 2, array $overrides = []): array
    {
        return array_merge([
            'order_type' => 'delivery',
            'payment_method' => 'cod',
            'customer_name' => 'Maria Santos',
            'customer_phone' => '0917 123 4567',
            'delivery_address' => '12 Mabini St, Poblacion',
            'notes' => 'Extra sauce please',
            'items' => [
                ['product_id' => $product->id, 'qty' => $qty, 'note' => 'No onions'],
            ],
        ], $overrides);
    }

    public function test_storefront_lists_only_online_products(): void
    {
        $visible = $this->product(['name' => 'Midnight Burger']);
        $hidden = $this->product(['name' => 'Hidden Special', 'is_online' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Midnight Burger')
            ->assertDontSee('Hidden Special');

        $this->assertTrue($visible->exists);
        $this->assertTrue($hidden->exists);
    }

    public function test_guest_can_place_a_delivery_order_and_stock_is_reserved(): void
    {
        $product = $this->product(['stock_qty' => 10]);

        $response = $this->postJson('/orders', $this->deliveryPayload($product, 2));

        $response->assertOk()->assertJson(['ok' => true]);

        $order = Order::query()->sole();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(OrderType::Delivery, $order->order_type);
        $this->assertSame(OrderPaymentMethod::CashOnDelivery, $order->payment_method);
        $this->assertSame('100.00', $order->subtotal);
        $this->assertSame('39.00', $order->delivery_fee);
        $this->assertSame('139.00', $order->total);
        $this->assertStringStartsWith('ORD-', $order->reference_no);
        $this->assertCount(1, $order->items);
        $this->assertSame('No onions', $order->items->first()->note);

        $this->assertSame(8, $product->fresh()->stock_qty);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::OnlineOrder->value,
            'qty_change' => -2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $response->assertJsonPath('redirect', route('orders.track', ['token' => $order->public_token]));
    }

    public function test_prices_come_from_the_database_not_the_client(): void
    {
        $product = $this->product(['selling_price' => '50.00']);

        $payload = $this->deliveryPayload($product, 1);
        $payload['items'][0]['unit_price'] = '1.00';
        $payload['total'] = '1.00';

        $this->postJson('/orders', $payload)->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('50.00', $order->subtotal);
        $this->assertSame('50.00', $order->items->first()->unit_price);
    }

    public function test_pickup_has_no_delivery_fee(): void
    {
        $product = $this->product(['selling_price' => '75.00']);

        $this->postJson('/orders', $this->deliveryPayload($product, 2, [
            'order_type' => 'pickup',
            'payment_method' => 'cash',
            'delivery_address' => null,
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame(OrderType::Pickup, $order->order_type);
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('150.00', $order->total);
        $this->assertTrue($order->isPickup());
    }

    public function test_free_delivery_threshold_waives_the_fee(): void
    {
        Setting::set('free_delivery_threshold', '100.00');

        $product = $this->product(['selling_price' => '60.00']);

        $this->postJson('/orders', $this->deliveryPayload($product, 2))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('120.00', $order->total);
    }

    public function test_gcash_reference_puts_the_order_awaiting_verification(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'payment_method' => 'gcash',
            'payment_reference' => '1234567890123',
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame(OrderPaymentMethod::GCash, $order->payment_method);
        $this->assertSame(PaymentStatus::AwaitingVerification, $order->payment_status);
        $this->assertSame('1234567890123', $order->payment_reference);
    }

    public function test_it_will_not_oversell(): void
    {
        $product = $this->product(['stock_qty' => 1]);

        $this->postJson('/orders', $this->deliveryPayload($product, 2))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock_qty);
    }

    public function test_offline_products_cannot_be_ordered(): void
    {
        $product = $this->product(['is_online' => false]);

        $this->postJson('/orders', $this->deliveryPayload($product, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_ordering_can_be_paused(): void
    {
        Setting::set('orders_paused', '1');

        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('order');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_delivery_order_requires_an_address(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'delivery_address' => '',
        ]))->assertStatus(422)->assertJsonValidationErrors('delivery_address');
    }

    public function test_minimum_order_is_enforced(): void
    {
        Setting::set('minimum_order_total', '200.00');

        $product = $this->product(['selling_price' => '50.00']);

        $this->postJson('/orders', $this->deliveryPayload($product, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_customer_can_track_the_order_with_the_token(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1))->assertOk();

        $order = Order::query()->sole();

        $this->get(route('orders.track', ['token' => $order->public_token]))
            ->assertOk()
            ->assertSee($order->reference_no)
            ->assertSee('Maria Santos');
    }

    public function test_customer_can_find_an_order_by_reference_and_phone(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1))->assertOk();

        $order = Order::query()->sole();

        $this->post('/track', [
            'reference' => mb_strtolower($order->reference_no),
            'phone' => '0917-123-4567',
        ])->assertRedirect(route('orders.track', ['token' => $order->public_token]));
    }

    public function test_lookup_rejects_the_wrong_phone(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1))->assertOk();

        $order = Order::query()->sole();

        $this->post('/track', [
            'reference' => $order->reference_no,
            'phone' => '0999 999 9999',
        ])->assertSessionHasErrors('reference');
    }
}
