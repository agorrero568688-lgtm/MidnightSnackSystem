<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\SaleService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        return User::factory()->create();
    }

    private function shiftFor(User $user): Shift
    {
        return app(ShiftService::class)->open($user, '100.00');
    }

    public function test_checkout_decrements_stock_and_records_the_sale(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '30.00', 'stock_qty' => 10]);

        $sale = app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 3]],
            PaymentMethod::Cash,
            '100.00',
        );

        $this->assertSame('90.00', $sale->total);
        $this->assertSame('10.00', $sale->change_due);
        $this->assertMatchesRegularExpression('/^DMS-\d{6}$/', $sale->reference_no);
        $this->assertSame(7, $product->fresh()->stock_qty);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 3,
            'unit_price' => '30.00',
            'line_total' => '90.00',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'qty_change' => -3,
            'qty_after' => 7,
        ]);
    }

    public function test_it_charges_the_database_price_even_if_the_client_sends_another(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '45.00', 'stock_qty' => 5]);

        $sale = app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 2, 'unit_price' => '1.00', 'price' => 1]],
            PaymentMethod::Cash,
            '90.00',
        );

        $this->assertSame('90.00', $sale->total);
    }

    public function test_senior_discount_takes_twenty_percent_and_requires_customer_details(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '100.00', 'stock_qty' => 5]);

        $sale = app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 1]],
            PaymentMethod::Cash,
            '100.00',
            DiscountType::Senior,
            'Lola Ising',
            'OSCA-12345',
        );

        $this->assertSame('100.00', $sale->subtotal);
        $this->assertSame('20.00', $sale->discount_amount);
        $this->assertSame('80.00', $sale->total);
        $this->assertSame('20.00', $sale->change_due);
        $this->assertSame('OSCA-12345', $sale->discount_id_number);

        $this->expectException(ValidationException::class);

        app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 1]],
            PaymentMethod::Cash,
            '100.00',
            DiscountType::Pwd,
        );
    }

    public function test_it_blocks_overselling_by_default(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '10.00', 'stock_qty' => 2]);

        $this->expectException(ValidationException::class);

        try {
            app(CheckoutService::class)->checkout(
                $user,
                $shift,
                [['product_id' => $product->id, 'qty' => 3]],
                PaymentMethod::Cash,
                '100.00',
            );
        } finally {
            $this->assertSame(0, Sale::query()->count());
            $this->assertSame(2, $product->fresh()->stock_qty);
        }
    }

    public function test_admin_can_allow_negative_stock(): void
    {
        Setting::set('allow_negative_stock', '1');

        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '10.00', 'stock_qty' => 1]);

        $sale = app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 3]],
            PaymentMethod::Cash,
            '100.00',
        );

        $this->assertSame('30.00', $sale->total);
        $this->assertSame(-2, $product->fresh()->stock_qty);
    }

    public function test_cash_tendered_must_cover_the_total(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '50.00', 'stock_qty' => 5]);

        $this->expectException(ValidationException::class);

        app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 2]],
            PaymentMethod::Cash,
            '50.00',
        );
    }

    public function test_voiding_a_sale_returns_stock_and_cannot_happen_twice(): void
    {
        $user = $this->cashier();
        $admin = User::factory()->admin()->create();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '25.00', 'stock_qty' => 10]);

        $sale = app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 4]],
            PaymentMethod::Cash,
            '100.00',
        );

        $this->assertSame(6, $product->fresh()->stock_qty);

        app(SaleService::class)->void($sale, $admin, 'Wrong item scanned');

        $this->assertSame(10, $product->fresh()->stock_qty);
        $this->assertSame('voided', $sale->fresh()->status->value);
        $this->assertSame($admin->id, $sale->fresh()->voided_by);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'void',
            'qty_change' => 4,
        ]);

        $this->expectException(ValidationException::class);
        app(SaleService::class)->void($sale->fresh(), $admin, 'Again');
    }

    public function test_shift_expected_cash_counts_only_completed_cash_sales(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '100.00', 'stock_qty' => 10]);

        app(CheckoutService::class)->checkout($user, $shift, [['product_id' => $product->id, 'qty' => 1]], PaymentMethod::Cash, '100.00');
        app(CheckoutService::class)->checkout($user, $shift, [['product_id' => $product->id, 'qty' => 1]], PaymentMethod::GCash, null);

        $this->assertSame(20000, $shift->fresh()->expectedCashCents());

        $closed = app(ShiftService::class)->close($shift->fresh(), '195.00');
        $this->assertSame('-5.00', $closed->variance);
    }
}
