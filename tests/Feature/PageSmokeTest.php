<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_back_office_screen_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'selling_price' => '30.00',
        ]);

        $shift = app(ShiftService::class)->open($cashier, '100.00');
        $sale = app(CheckoutService::class)->checkout(
            $cashier,
            $shift,
            [['product_id' => $product->id, 'qty' => 2]],
            PaymentMethod::Cash,
            '100.00',
        );

        $routes = [
            '/dashboard',
            '/products',
            '/products/create',
            '/products/'.$product->id.'/edit',
            '/categories',
            '/sales',
            '/shifts',
            '/reports/daily',
            '/users',
            '/settings',
            '/pos',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }

        $this->actingAs($admin)->get('/sales/'.$sale->id.'/receipt')->assertOk();
    }

    public function test_cashier_can_render_the_cashier_screen_and_reprint_a_receipt(): void
    {
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '12.50']);
        $shift = app(ShiftService::class)->open($cashier, '0');
        $sale = app(CheckoutService::class)->checkout(
            $cashier,
            $shift,
            [['product_id' => $product->id, 'qty' => 1]],
            PaymentMethod::Cash,
            '20.00',
        );

        $this->actingAs($cashier)->get('/pos')->assertOk()->assertSee('Scan barcode');
        $this->actingAs($cashier)->get('/sales/'.$sale->id.'/receipt')->assertOk()->assertSee($sale->reference_no);
    }

    public function test_csv_export_streams(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => '10.00']);
        $shift = app(ShiftService::class)->open($cashier, '0');

        app(CheckoutService::class)->checkout(
            $cashier,
            $shift,
            [['product_id' => $product->id, 'qty' => 1]],
            PaymentMethod::Cash,
            '10.00',
        );

        $response = $this->actingAs($admin)->get('/reports/daily/export?date='.today()->toDateString());

        $response->assertOk();
        $this->assertStringContainsString('Reference', $response->streamedContent());
    }
}
