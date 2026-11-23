<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SalesChannel;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Livewire\Orders\Board;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OrderBoardTest extends TestCase
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

    private function place(Product $product, int $qty = 2): Order
    {
        return app(OrderService::class)->place([
            'order_type' => 'delivery',
            'payment_method' => 'gcash',
            'customer_name' => 'Maria Santos',
            'customer_phone' => '0917 123 4567',
            'delivery_address' => '12 Mabini St, Poblacion',
            'payment_reference' => '1234567890123',
            'items' => [
                ['product_id' => $product->id, 'qty' => $qty, 'note' => null],
            ],
        ]);
    }

    public function test_guests_cannot_see_the_order_board(): void
    {
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_staff_can_open_the_order_board(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product);

        $this->actingAs($cashier)->get('/orders')->assertOk()->assertSee($order->reference_no);
    }

    public function test_staff_can_move_an_order_through_to_a_sale(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product, 2);

        Livewire::actingAs($cashier)
            ->test(Board::class)
            ->call('openOrder', $order->id)
            ->call('confirmOrder')
            ->call('startPreparing')
            ->call('markReady')
            ->call('markPaid')
            ->call('completeOrder')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertNotNull($order->sale_id);
        $this->assertNotNull($order->completed_at);

        $sale = Sale::query()->findOrFail($order->sale_id);

        $this->assertSame(SalesChannel::Online, $sale->channel);
        $this->assertSame(SaleStatus::Completed, $sale->status);
        $this->assertSame('100.00', $sale->subtotal);
        $this->assertSame('39.00', $sale->delivery_fee);
        $this->assertSame('139.00', $sale->total);
        $this->assertSame($cashier->id, $sale->user_id);
        $this->assertStringStartsWith('DMS-', $sale->reference_no);
        $this->assertCount(1, $sale->items);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Sale->value,
            'qty_change' => -2,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
        ]);

        $this->assertDatabaseMissing('stock_movements', ['reference_type' => 'order']);

        // Stock was reserved at placement, so completing must not deduct again.
        $this->assertSame(8, $product->fresh()->stock_qty);
    }

    public function test_an_order_cannot_be_completed_before_payment(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product);

        Livewire::actingAs($cashier)
            ->test(Board::class)
            ->call('openOrder', $order->id)
            ->call('confirmOrder')
            ->call('startPreparing')
            ->call('markReady')
            ->call('completeOrder')
            ->assertSet('flashError', 'Record the payment before completing this order.');

        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_cancelling_returns_reserved_stock(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product, 2);

        $this->assertSame(8, $product->fresh()->stock_qty);

        Livewire::actingAs($cashier)
            ->test(Board::class)
            ->call('openOrder', $order->id)
            ->set('showCancel', true)
            ->set('cancelReason', 'Customer called to cancel')
            ->call('cancelOrder')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock_qty);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Void->value,
            'qty_change' => 2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_staff_can_apply_a_verified_senior_discount(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product, 2);

        Livewire::actingAs($cashier)
            ->test(Board::class)
            ->call('openOrder', $order->id)
            ->set('discountType', DiscountType::Senior->value)
            ->set('discountName', 'Maria Santos')
            ->set('discountIdNumber', 'OSCA-12345')
            ->call('applyDiscount')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(DiscountType::Senior, $order->discount_type);
        $this->assertSame(20, (int) $order->discount_rate);
        $this->assertSame('20.00', $order->discount_amount);
        $this->assertSame('119.00', $order->total);
        $this->assertSame('OSCA-12345', $order->discount_id_number);
    }

    public function test_a_discount_needs_a_name_and_id(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product);

        Livewire::actingAs($cashier)
            ->test(Board::class)
            ->call('openOrder', $order->id)
            ->set('discountType', DiscountType::Pwd->value)
            ->call('applyDiscount')
            ->assertSet('flashError', 'Customer name and OSCA/PWD ID number are required for this discount.');

        $this->assertSame('139.00', $order->fresh()->total);
    }

    public function test_the_total_is_locked_once_payment_is_recorded(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product);

        app(OrderService::class)->markPaid($order, $cashier);

        $this->expectException(ValidationException::class);

        app(OrderService::class)->applyDiscount(
            $order->fresh(),
            $cashier,
            DiscountType::Senior,
            'Maria Santos',
            'OSCA-12345',
        );
    }

    public function test_a_cancelled_paid_order_can_be_marked_refunded(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 10]);
        $order = $this->place($product);

        $service = app(OrderService::class);
        $service->markPaid($order, $cashier);
        $service->cancel($order->fresh(), $cashier, 'Out of stock');
        $service->markRefunded($order->fresh(), $cashier);

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
    }
}
