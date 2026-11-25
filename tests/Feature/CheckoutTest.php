<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
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

    public function test_checkout_records_the_sale(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '30.00']);

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

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'qty' => 3,
            'unit_price' => '30.00',
            'line_total' => '90.00',
        ]);
    }

    public function test_it_charges_the_database_price_even_if_the_client_sends_another(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '45.00']);

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
        $product = Product::factory()->create(['selling_price' => '100.00']);

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

    public function test_it_blocks_products_marked_as_sold_out(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->unavailable()->create(['selling_price' => '10.00']);

        $this->expectException(ValidationException::class);

        try {
            app(CheckoutService::class)->checkout(
                $user,
                $shift,
                [['product_id' => $product->id, 'qty' => 1]],
                PaymentMethod::Cash,
                '100.00',
            );
        } finally {
            $this->assertSame(0, Sale::query()->count());
        }
    }

    public function test_multiple_sales_do_not_reduce_product_availability(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '10.00']);

        app(CheckoutService::class)->checkout($user, $shift, [['product_id' => $product->id, 'qty' => 3]], PaymentMethod::Cash, '100.00');
        app(CheckoutService::class)->checkout($user, $shift, [['product_id' => $product->id, 'qty' => 3]], PaymentMethod::Cash, '100.00');

        $this->assertTrue($product->fresh()->is_available);
        $this->assertDatabaseCount('sales', 2);
    }

    public function test_cash_tendered_must_cover_the_total(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '50.00']);

        $this->expectException(ValidationException::class);

        app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 2]],
            PaymentMethod::Cash,
            '50.00',
        );
    }

    public function test_voiding_a_sale_changes_status_and_cannot_happen_twice(): void
    {
        $user = $this->cashier();
        $admin = User::factory()->admin()->create();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '25.00']);

        $sale = app(CheckoutService::class)->checkout(
            $user,
            $shift,
            [['product_id' => $product->id, 'qty' => 4]],
            PaymentMethod::Cash,
            '100.00',
        );

        app(SaleService::class)->void($sale, $admin, 'Wrong item scanned');

        $this->assertSame('voided', $sale->fresh()->status->value);
        $this->assertSame($admin->id, $sale->fresh()->voided_by);

        $this->expectException(ValidationException::class);
        app(SaleService::class)->void($sale->fresh(), $admin, 'Again');
    }

    public function test_shift_expected_cash_counts_only_completed_cash_sales(): void
    {
        $user = $this->cashier();
        $shift = $this->shiftFor($user);
        $product = Product::factory()->create(['selling_price' => '100.00']);

        app(CheckoutService::class)->checkout($user, $shift, [['product_id' => $product->id, 'qty' => 1]], PaymentMethod::Cash, '100.00');
        app(CheckoutService::class)->checkout($user, $shift, [['product_id' => $product->id, 'qty' => 1]], PaymentMethod::GCash, null);

        $this->assertSame(20000, $shift->fresh()->expectedCashCents());

        $closed = app(ShiftService::class)->close($shift->fresh(), '195.00');
        $this->assertSame('-5.00', $closed->variance);
    }
}
