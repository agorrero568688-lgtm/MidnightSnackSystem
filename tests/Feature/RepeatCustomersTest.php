<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepeatCustomersTest extends TestCase
{
    use RefreshDatabase;

    private function placeFor(string $phone, Product $product): Order
    {
        return app(OrderService::class)->place([
            'order_type' => 'pickup',
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => $phone,
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);
    }

    public function test_suggestions_for_a_repeat_customer(): void
    {
        $product = Product::factory()->create();

        $this->placeFor('09171234567', $product);
        $this->placeFor('09171234567', $product);

        $suggestions = app(OrderService::class)->suggestionsForPhone('09171234567');

        $this->assertCount(1, $suggestions);
        $this->assertSame($product->id, $suggestions->first()->id);
    }

    public function test_no_suggestions_for_a_first_time_phone(): void
    {
        $product = Product::factory()->create();

        $this->placeFor('09171234567', $product);

        $suggestions = app(OrderService::class)->suggestionsForPhone('0917-987-6543');

        $this->assertCount(0, $suggestions);
    }

    public function test_suggestions_only_include_online_products(): void
    {
        $product = Product::factory()->create();

        $this->placeFor('09171234567', $product);
        $this->placeFor('09171234567', $product);

        $product->update(['is_online' => false]);

        $suggestions = app(OrderService::class)->suggestionsForPhone('09171234567');

        $this->assertCount(0, $suggestions);
    }

    public function test_suggestions_endpoint_returns_json(): void
    {
        $product = Product::factory()->create(['name' => 'Repeat Chips']);

        $this->placeFor('09171234567', $product);
        $this->placeFor('09171234567', $product);

        $this->get('/suggestions?phone=09171234567')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Repeat Chips')
            ->assertJsonPath('0.price_cents', Money::cents($product->selling_price));
    }

    public function test_suggestions_endpoint_for_an_unknown_phone(): void
    {
        $this->get('/suggestions?phone=09170000000')
            ->assertOk()
            ->assertJson([]);
    }
}
