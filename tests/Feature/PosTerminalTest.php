<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_open_a_shift_from_the_till(): void
    {
        $cashier = User::factory()->create();

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openShift', '150.00')
            ->assertReturned(fn (array $result) => $result['ok'] === true
                && $result['shift']['opening_cents'] === 15000);

        $this->assertDatabaseHas('shifts', [
            'user_id' => $cashier->id,
            'status' => 'open',
            'opening_cash' => '150.00',
        ]);
    }

    public function test_checkout_from_the_till_returns_the_receipt_payload(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '30.00', 'stock_qty' => 10]);

        $component = Livewire::actingAs($cashier)->test(Terminal::class);
        $component->call('openShift', '0');

        $component->call('checkout', [
            'items' => [['product_id' => $product->id, 'qty' => 2]],
            'payment_method' => 'cash',
            'amount_tendered' => '100.00',
            'discount_type' => 'none',
        ])->assertReturned(fn (array $result) => $result['ok'] === true
            && $result['sale']['total_cents'] === 6000
            && $result['sale']['change_cents'] === 4000
            && $result['sale']['item_count'] === 2
            && $result['stock'][$product->id] === 8
            && str_contains($result['sale']['receipt_url'], '/receipt'));

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(8, $product->fresh()->stock_qty);
    }

    public function test_checkout_is_refused_without_an_open_shift_when_required(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '10.00', 'stock_qty' => 5]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('checkout', [
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'payment_method' => 'cash',
                'amount_tendered' => '10.00',
                'discount_type' => 'none',
            ])
            ->assertReturned(fn (array $result) => $result['ok'] === false
                && str_contains($result['message'], 'Open a shift'));

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_checkout_reports_insufficient_stock_without_throwing(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '10.00', 'stock_qty' => 1]);

        $component = Livewire::actingAs($cashier)->test(Terminal::class);
        $component->call('openShift', '0');

        $component->call('checkout', [
            'items' => [['product_id' => $product->id, 'qty' => 5]],
            'payment_method' => 'cash',
            'amount_tendered' => '100.00',
            'discount_type' => 'none',
        ])->assertReturned(fn (array $result) => $result['ok'] === false
            && str_contains($result['message'], 'Not enough stock'));

        $this->assertSame(1, $product->fresh()->stock_qty);
    }

    public function test_senior_discount_requires_customer_details_at_the_till(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '100.00', 'stock_qty' => 5]);

        $component = Livewire::actingAs($cashier)->test(Terminal::class);
        $component->call('openShift', '0');

        $component->call('checkout', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'payment_method' => 'cash',
            'amount_tendered' => '100.00',
            'discount_type' => 'senior',
        ])->assertReturned(fn (array $result) => $result['ok'] === false);

        $component->call('checkout', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'payment_method' => 'cash',
            'amount_tendered' => '100.00',
            'discount_type' => 'senior',
            'customer_name' => 'Lola Ising',
            'discount_id_number' => 'OSCA-1',
        ])->assertReturned(fn (array $result) => $result['ok'] === true
            && $result['sale']['total_cents'] === 8000);
    }
}
