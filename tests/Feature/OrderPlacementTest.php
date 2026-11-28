<?php

namespace Tests\Feature;

use App\Enums\OrderPaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Jobs\SendOrderSmsAlert;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\SmsGateway;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1x1 PNG so MIME sniffing returns image/png. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

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
            'delivery_fee' => '20.00',
            'free_delivery_threshold' => '0',
            'minimum_order_total' => '0',
            'order_alerts_enabled' => '0',
        ]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'selling_price' => '50.00',
            'is_active' => true,
            'is_online' => true,
            'is_available' => true,
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

    public function test_checkout_page_shows_the_gcash_receipt_upload(): void
    {
        $this->get('/order')
            ->assertOk()
            ->assertSee('GCash receipt screenshot')
            ->assertSee('Upload receipt screenshot')
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertDontSee('GCash reference number');
    }

    public function test_checkout_page_shows_the_configured_gcash_qr(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('store-settings/qr.png', base64_decode(self::PNG));
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $url = route('gcash.qr', [
            'v' => Storage::disk('public')->lastModified('store-settings/qr.png'),
        ], false);

        $this->get('/order')
            ->assertOk()
            ->assertSee($url, false)
            ->assertDontSee('GCash QR not set');
    }

    public function test_checkout_page_shows_the_public_disk_media_url(): void
    {
        config([
            'filesystems.disks.public.driver' => 's3',
            'filesystems.disks.public.url' => 'https://media.example.com/products',
        ]);
        Storage::fake('public');
        Setting::set('gcash_qr_path', 'store-settings/qr.png');

        $this->get('/order')
            ->assertOk()
            ->assertSee('https://media.example.com/products/store-settings/qr.png', false)
            ->assertDontSee('GCash QR not set');
    }

    public function test_guest_can_place_a_delivery_order(): void
    {
        $product = $this->product();

        $response = $this->postJson('/orders', $this->deliveryPayload($product, 2));

        $response->assertOk()->assertJson(['ok' => true]);

        $order = Order::query()->sole();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(OrderType::Delivery, $order->order_type);
        $this->assertSame(OrderPaymentMethod::CashOnDelivery, $order->payment_method);
        $this->assertSame('100.00', $order->subtotal);
        $this->assertSame('20.00', $order->delivery_fee);
        $this->assertSame('120.00', $order->total);
        $this->assertStringStartsWith('ORD-', $order->reference_no);
        $this->assertCount(1, $order->items);
        $this->assertSame('No onions', $order->items->first()->note);

        $response->assertJsonPath('redirect', route('orders.track', ['token' => $order->public_token]));
    }

    public function test_a_failed_sms_alert_does_not_break_checkout(): void
    {
        // setUp() installs a catch-all fake; start from a clean factory so the
        // gateway really answers with an error.
        Http::swap(new Factory);
        Http::fake(['*' => Http::response('nope', 500)]);

        $product = $this->product();

        Setting::setMany([
            'order_alerts_enabled' => '1',
            'order_alert_phones' => '09171234567',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        $this->postJson('/orders', $this->deliveryPayload($product, 1))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseCount('orders', 1);

        $order = Order::query()->sole();
        $this->assertNull($order->alert_sent_at);

        // The queued job fails so it is retried, but the order itself was
        // already saved and is never marked as alerted.
        $this->expectException(\RuntimeException::class);

        app()->make(SendOrderSmsAlert::class, ['orderId' => $order->id])
            ->handle(app(SmsGateway::class));
    }

    public function test_a_new_order_texts_every_admin_number(): void
    {
        $product = $this->product();

        Setting::setMany([
            'order_alerts_enabled' => '1',
            'order_alert_phones' => '09171234567, 09181234567',
            'sms_gateway_url' => 'https://api.gateway.com/sms',
        ]);

        Http::fake(['api.gateway.com/*' => Http::response(['ok' => true], 200)]);

        $this->postJson('/orders', $this->deliveryPayload($product, 1))->assertOk();

        $order = Order::query()->sole();

        app()->make(SendOrderSmsAlert::class, ['orderId' => $order->id])
            ->handle(app(SmsGateway::class));

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['to'] === '09171234567'
            && str_contains($request['message'], $order->reference_no));
        Http::assertSent(fn ($request) => $request['to'] === '09181234567');

        $this->assertNotNull($order->fresh()->alert_sent_at);
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

    public function test_free_delivery_barangays_are_free(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 2, [
            'delivery_address' => '#5, Purok 3, Magosilom, Cantilan Proper',
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('100.00', $order->total);
    }

    public function test_lininti_an_is_free_too(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'delivery_address' => '#12 Lininti-an, Cantilan Proper',
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('0.00', $order->delivery_fee);
    }

    public function test_other_barangays_pay_the_fee(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'delivery_address' => 'Brgy. Bugsukan, Cantilan Proper',
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('20.00', $order->delivery_fee);
        $this->assertSame('70.00', $order->total);
    }

    public function test_the_free_zone_match_is_case_and_punctuation_insensitive(): void
    {
        Setting::set('delivery_free_barangays', 'Cabangahan');

        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'delivery_address' => '4th St., BRGY. Cabangahan, Cantilan',
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('0.00', $order->delivery_fee);
    }

    public function test_an_address_without_the_barangay_name_charges_the_fee(): void
    {
        $product = $this->product();

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'delivery_address' => '#1 North Road, Consuelo, Cantilan Proper',
        ]))->assertOk();

        $order = Order::query()->sole();

        $this->assertSame('20.00', $order->delivery_fee);
    }

    public function test_gcash_order_requires_a_payment_screenshot(): void
    {
        $product = $this->product();

        $this->post('/orders', $this->deliveryPayload($product, 1, [
            'payment_method' => 'gcash',
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_proof');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_rejected_order_does_not_leave_a_stored_receipt(): void
    {
        Storage::fake('local');

        try {
            app(OrderService::class)->place([
                'order_type' => 'delivery',
                'payment_method' => 'gcash',
                'customer_name' => 'Maria Santos',
                'customer_phone' => '0917 123 4567',
                'delivery_address' => '12 Mabini St, Poblacion',
                'payment_proof' => $this->fakeImageUpload('receipt.png'),
                'items' => [],
            ]);

            $this->fail('Expected the empty cart to be rejected.');
        } catch (ValidationException) {
            // Expected.
        }

        $this->assertSame([], Storage::disk('local')->allFiles('order-proofs'));
    }

    public function test_a_failed_receipt_upload_is_reported_as_a_validation_error(): void
    {
        $product = $this->product();

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldIgnoreMissing();
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::set('local', $disk);

        $this->postJson('/orders', $this->deliveryPayload($product, 1, [
            'payment_method' => 'gcash',
            'payment_proof' => $this->fakeImageUpload('receipt.png'),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_proof');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_gcash_screenshot_puts_the_order_awaiting_verification(): void
    {
        Storage::fake('local');

        $product = $this->product();

        $this->post('/orders', $this->deliveryPayload($product, 1, [
            'payment_method' => 'gcash',
            'payment_proof' => $this->fakeImageUpload('receipt.png'),
        ]), ['Accept' => 'application/json'])->assertOk();

        $order = Order::query()->sole();

        $this->assertSame(OrderPaymentMethod::GCash, $order->payment_method);
        $this->assertSame(PaymentStatus::AwaitingVerification, $order->payment_status);
        $this->assertNull($order->payment_reference);
        $this->assertNotNull($order->payment_proof_path);
        Storage::disk('local')->assertExists($order->payment_proof_path);
    }

    public function test_customer_can_replace_the_receipt_from_the_tracking_page(): void
    {
        Storage::fake('local');

        $product = $this->product();

        $this->post('/orders', $this->deliveryPayload($product, 1, [
            'payment_method' => 'gcash',
            'payment_proof' => $this->fakeImageUpload('first.png'),
        ]), ['Accept' => 'application/json'])->assertOk();

        $order = Order::query()->sole();
        $firstPath = $order->payment_proof_path;

        $this->post(route('orders.payment', ['token' => $order->public_token]), [
            'proof' => $this->fakeImageUpload('second.png'),
        ])->assertRedirect(route('orders.track', ['token' => $order->public_token]));

        $order->refresh();

        $this->assertSame(PaymentStatus::AwaitingVerification, $order->payment_status);
        $this->assertNotSame($firstPath, $order->payment_proof_path);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($order->payment_proof_path);
    }

    public function test_products_marked_as_sold_out_cannot_be_ordered(): void
    {
        $product = $this->product(['is_available' => false]);

        $this->postJson('/orders', $this->deliveryPayload($product, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
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
